<?php
require_once __DIR__ . '/functions.php';

$courses = get_courses();
$errors = [];
$values = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'course_id' => '',
    'gender' => '',
    'dob' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$errors, $clean] = validate_student($_POST, $_FILES, true);
    $values = array_merge($values, [
        'name' => $clean['name'],
        'email' => $clean['email'],
        'phone' => $clean['phone'],
        'course_id' => $clean['course_id'] ?? '',
        'gender' => $clean['gender'] ?? '',
        'dob' => $clean['dob'] ?? '',
    ]);

    if (!$errors) {
        $image_name = save_uploaded_image($_FILES['profile_image']);

        $stmt = db()->prepare(
            "INSERT INTO students (name, email, phone, course_id, gender, dob, profile_image)
             VALUES (?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)"
        );

        $p_name = $clean['name'];
        $p_email = $clean['email'];
        $p_phone = $clean['phone'];
        $p_course_id = (string)($clean['course_id'] ?? '');
        $p_gender = (string)($clean['gender'] ?? '');
        $p_dob = (string)($clean['dob'] ?? '');
        $p_image = $image_name;

        $stmt->bind_param(
            "sssssss",
            $p_name,
            $p_email,
            $p_phone,
            $p_course_id,
            $p_gender,
            $p_dob,
            $p_image
        );
        try {
            $stmt->execute();
            header("Location: index.php?msg=" . urlencode("Student added successfully."));
            exit;
        } catch (mysqli_sql_exception $e) {
            $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $image_name;
            if (is_file($path)) {
                @unlink($path);
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
    <h1 class="h4 mb-1">Add Student</h1>
    <div class="text-secondary">Fill the form to register a new student.</div>
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
        <input type="file" class="form-control <?= isset($errors['profile_image']) ? 'is-invalid' : '' ?>" name="profile_image" accept=".jpg,.jpeg,.png" required>
        <div class="invalid-feedback"><?= h($errors['profile_image'] ?? '') ?></div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

