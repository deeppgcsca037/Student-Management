<?php
require_once __DIR__ . '/db.php';

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function ensure_upload_dir(): void
{
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
}

function get_courses(): array
{
    $rows = [];
    $res = db()->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

function validate_student(array $post, array $files, bool $image_required = true): array
{
    $errors = [];

    $name = trim((string)($post['name'] ?? ''));
    $email = trim((string)($post['email'] ?? ''));
    $phone = trim((string)($post['phone'] ?? ''));
    $course_id = $post['course_id'] ?? null;
    $gender = $post['gender'] ?? null;
    $dob = trim((string)($post['dob'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'Full Name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'A valid Email is required.';
    }

    if ($phone !== '' && !preg_match('/^[0-9]+$/', $phone)) {
        $errors['phone'] = 'Phone must be numeric.';
    }

    if ($gender !== null && $gender !== '' && !in_array($gender, ['Male', 'Female'], true)) {
        $errors['gender'] = 'Gender must be Male or Female.';
    }

    if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        $errors['dob'] = 'Date of Birth must be a valid date.';
    }

    if ($course_id === '' || $course_id === null) {
        $course_id = null;
    } elseif (!ctype_digit((string)$course_id)) {
        $errors['course_id'] = 'Course is invalid.';
    }

    $image = $files['profile_image'] ?? null;
    $image_was_uploaded = is_array($image) && ($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($image_required && !$image_was_uploaded) {
        $errors['profile_image'] = 'Profile image is required.';
    }

    if ($image_was_uploaded) {
        if (($image['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $errors['profile_image'] = 'Image upload failed.';
        } else {
            if (($image['size'] ?? 0) > MAX_UPLOAD_BYTES) {
                $errors['profile_image'] = 'Image too large (max 2MB).';
            }

            $tmp = $image['tmp_name'] ?? '';
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $tmp ? $finfo->file($tmp) : '';
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($allowed[$mime])) {
                $errors['profile_image'] = 'Image must be JPG or PNG.';
            }
        }
    }

    return [$errors, [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'course_id' => $course_id === null ? null : (int)$course_id,
        'gender' => ($gender === '' ? null : $gender),
        'dob' => ($dob === '' ? null : $dob),
        'image_uploaded' => $image_was_uploaded,
    ]];
}

function save_uploaded_image(array $file): string
{
    ensure_upload_dir();

    $tmp = $file['tmp_name'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $ext = $mime === 'image/png' ? 'png' : 'jpg';

    $name = 'student_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target = UPLOAD_DIR . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return $name;
}

