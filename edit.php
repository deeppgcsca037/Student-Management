<?php
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?msg=" . urlencode("Invalid student id."));
    exit;
}

$stmt = db()->prepare(
    "SELECT student_id, name, email, phone, course_id, gender, dob, profile_image
     FROM students WHERE student_id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: index.php?msg=" . urlencode("Student not found."));
    exit;
}

$courses = get_courses();
$errors = [];
$values = [
    'name' => (string)$student['name'],
    'email' => (string)$student['email'],
    'phone' => (string)($student['phone'] ?? ''),
    'course_id' => (string)($student['course_id'] ?? ''),
    'gender' => (string)($student['gender'] ?? ''),
    'dob' => (string)($student['dob'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $clean] = validate_student($_POST, $_FILES, false);
    $values = array_merge($values, [
        'name' => $clean['name'],
        'email' => $clean['email'],
        'phone' => $clean['phone'],
        'course_id' => $clean['course_id'] ?? '',
        'gender' => $clean['gender'] ?? '',
        'dob' => $clean['dob'] ?? '',
    ]);

    if (!$errors) {
        $new_image = $student['profile_image'];
        $uploaded_this_request = false;
        $old_to_delete = null;

        if ($clean['image_uploaded']) {
            $new_image = save_uploaded_image($_FILES['profile_image']);
            $uploaded_this_request = true;
            $old_to_delete = !empty($student['profile_image']) ? (string)$student['profile_image'] : null;
        }

        $stmt = db()->prepare(
            "UPDATE students
             SET name = ?, email = ?, phone = ?,
                 course_id = NULLIF(?, ''),
                 gender = NULLIF(?, ''),
                 dob = NULLIF(?, ''),
                 profile_image = ?
             WHERE student_id = ?"
        );

        $p_name = $clean['name'];
        $p_email = $clean['email'];
        $p_phone = $clean['phone'];
        $p_course_id = (string)($clean['course_id'] ?? '');
        $p_gender = (string)($clean['gender'] ?? '');
        $p_dob = (string)($clean['dob'] ?? '');
        $p_image = (string)$new_image;
        $p_id = $id;

        $stmt->bind_param(
            "sssssssi",
            $p_name,
            $p_email,
            $p_phone,
            $p_course_id,
            $p_gender,
            $p_dob,
            $p_image,
            $p_id
        );
        try {
            $stmt->execute();
            if ($old_to_delete) {
                $old_path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $old_to_delete;
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }
            header("Location: index.php?msg=" . urlencode("Student updated successfully."));
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($uploaded_this_request && !empty($new_image)) {
                $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $new_image;
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            if ((int)$e->getCode() === 1062) {
                $errors['email'] = 'This email already exists.';
            } else {
                throw $e;
            }
        }
    }
}

require __DIR__ . '/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h1 class="h4 mb-1">Edit Student</h1>
    <div class="text-secondary">Update student details.</div>
  </div>
  <a class="btn btn-outline-dark" href="index.php">Back</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-12">
        <label class="form-label">Full Name</label>
        <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" name="name" value="<?= h($values['name']) ?>" required>
        <div class="invalid-feedback"><?= h($errors['name'] ?? '') ?></div>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Email</label>
        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" name="email" value="<?= h($values['email']) ?>" required>
        <div class="invalid-feedback"><?= h($errors['email'] ?? '') ?></div>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Phone Number</label>
        <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" name="phone" value="<?= h($values['phone']) ?>" inputmode="numeric">
        <div class="invalid-feedback"><?= h($errors['phone'] ?? '') ?></div>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Course</label>
        <select class="form-select <?= isset($errors['course_id']) ? 'is-invalid' : '' ?>" name="course_id">
          <option value="">Select a course</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['course_id'] ?>" <?= ((string)$values['course_id'] === (string)$c['course_id']) ? 'selected' : '' ?>>
              <?= h($c['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div class="invalid-feedback"><?= h($errors['course_id'] ?? '') ?></div>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label d-block">Gender</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="gender" value="Male" <?= ($values['gender'] === 'Male') ? 'checked' : '' ?>>
          <label class="form-check-label">Male</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="gender" value="Female" <?= ($values['gender'] === 'Female') ? 'checked' : '' ?>>
          <label class="form-check-label">Female</label>
        </div>
        <?php if (isset($errors['gender'])): ?>
          <div class="text-danger small mt-1"><?= h($errors['gender']) ?></div>
        <?php endif; ?>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Date of Birth</label>
        <input type="date" class="form-control <?= isset($errors['dob']) ? 'is-invalid' : '' ?>" name="dob" value="<?= h($values['dob']) ?>">
        <div class="invalid-feedback"><?= h($errors['dob'] ?? '') ?></div>
      </div>

      <div class="col-12 col-md-6">
        <label class="form-label">Profile Image (JPG/PNG)</label>
        <input type="file" class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>" name="profile_image" accept=".jpg,.jpeg,.png">
        <div class="invalid-feedback"><?= h($errors['profile_image'] ?? '') ?></div>
        <?php if (!empty($student['profile_image'])): ?>
          <div class="small text-secondary mt-2">
            Current:
            <img class="rounded border ms-2" style="width:40px;height:40px;object-fit:cover" src="<?= h(UPLOAD_URL . '/' . $student['profile_image']) ?>" alt="Current">
          </div>
        <?php endif; ?>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Update</button>
        <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

