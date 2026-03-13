<?php
require_once __DIR__ . '/functions.php';

$q = trim((string)($_GET['q'] ?? ''));

$sql = "SELECT s.student_id, s.name, s.email, s.phone, s.gender, s.dob, s.profile_image, c.course_name
        FROM students s
        LEFT JOIN courses c ON c.course_id = s.course_id";
$params = [];
$types = '';

if ($q !== '') {
    $sql .= " WHERE s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ?";
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
    $types = 'sss';
}

$sql .= " ORDER BY s.student_id DESC";

$stmt = db()->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$students = $res->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/header.php';
?>

<div class="row g-3 align-items-end mb-3">
  <div class="col-12 col-lg-8">
    <h1 class="h3 mb-1">Student Management System</h1>
    <div class="text-secondary">Add, search, edit, and delete students.</div>
  </div>
  <div class="col-12 col-lg-4">
    <form class="d-flex gap-2" method="get" action="index.php">
      <input class="form-control" type="search" name="q" value="<?= h($q) ?>" placeholder="Search by name, email, phone">
      <button class="btn btn-outline-dark" type="submit">Search</button>
    </form>
  </div>
</div>

<?php if (!empty($_GET['msg'])): ?>
  <div class="alert alert-success"><?= h($_GET['msg']) ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:80px;">ID</th>
            <th style="width:80px;">Image</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Course</th>
            <th>Gender</th>
            <th>DOB</th>
            <th style="width:160px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$students): ?>
          <tr><td colspan="9" class="text-center text-secondary py-4">No students found.</td></tr>
        <?php else: ?>
          <?php foreach ($students as $s): ?>
            <tr>
              <td class="fw-semibold"><?= (int)$s['student_id'] ?></td>
              <td>
                <?php if (!empty($s['profile_image'])): ?>
                  <img class="rounded avatar border" src="<?= h(UPLOAD_URL . '/' . $s['profile_image']) ?>" alt="Profile">
                <?php else: ?>
                  <div class="rounded avatar border bg-white d-flex align-items-center justify-content-center text-secondary">—</div>
                <?php endif; ?>
              </td>
              <td><?= h($s['name']) ?></td>
              <td><?= h($s['email']) ?></td>
              <td><?= h($s['phone']) ?></td>
              <td><?= h($s['course_name']) ?></td>
              <td><?= h($s['gender']) ?></td>
              <td><?= h($s['dob']) ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a class="btn btn-sm btn-primary" href="edit.php?id=<?= (int)$s['student_id'] ?>">Edit</a>
                  <form method="post" action="delete.php" onsubmit="return confirm('Delete this student?');">
                    <input type="hidden" name="id" value="<?= (int)$s['student_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>

