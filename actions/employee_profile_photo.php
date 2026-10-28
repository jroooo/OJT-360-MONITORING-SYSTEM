<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

function profile_photo_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (
    !isset($_SESSION['employee_logged_in'], $_SESSION['employee_id']) ||
    $_SESSION['employee_logged_in'] !== true ||
    (string) $_SESSION['employee_id'] === ''
) {
    profile_photo_response(401, ['ok' => false, 'message' => 'Please sign in again to change your profile photo.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    profile_photo_response(405, ['ok' => false, 'message' => 'POST is required.']);
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['employee_profile_photo_csrf'] ?? '';
if (!is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    profile_photo_response(403, ['ok' => false, 'message' => 'The request could not be verified. Refresh the page and try again.']);
}

$file = $_FILES['profile_picture'] ?? null;
if (!is_array($file)) {
    profile_photo_response(422, ['ok' => false, 'message' => 'Choose a photo to upload.']);
}

$uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
    profile_photo_response(422, ['ok' => false, 'message' => 'Choose an image no larger than 3 MB.']);
}
if ($uploadError !== UPLOAD_ERR_OK) {
    profile_photo_response(422, ['ok' => false, 'message' => 'The photo upload did not complete. Please try again.']);
}

$temporaryPath = $file['tmp_name'] ?? '';
if (
    !is_string($temporaryPath) ||
    !is_uploaded_file($temporaryPath) ||
    (int) ($file['size'] ?? 0) > 3 * 1024 * 1024
) {
    profile_photo_response(422, ['ok' => false, 'message' => 'Choose an image no larger than 3 MB.']);
}

$imageInfo = @getimagesize($temporaryPath);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (
    $imageInfo === false ||
    !isset($allowedTypes[$mime]) ||
    (int) $imageInfo[0] < 1 ||
    (int) $imageInfo[1] < 1 ||
    (int) $imageInfo[0] * (int) $imageInfo[1] > 20000000
) {
    profile_photo_response(422, ['ok' => false, 'message' => 'Only valid JPG, PNG, or WEBP photos are allowed.']);
}

$photoKey = hash('sha256', (string) $_SESSION['employee_id']);
$photoDirectory = __DIR__ . '/../uploads/employee-profile/' . $photoKey;
if (!is_dir($photoDirectory) && !mkdir($photoDirectory, 0755, true) && !is_dir($photoDirectory)) {
    profile_photo_response(500, ['ok' => false, 'message' => 'The profile photo folder could not be created.']);
}

$photoName = 'photo_' . bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
$photoPath = $photoDirectory . '/' . $photoName;
if (!move_uploaded_file($temporaryPath, $photoPath)) {
    profile_photo_response(500, ['ok' => false, 'message' => 'The profile photo could not be saved. Please try again.']);
}

$existingPhotos = glob($photoDirectory . '/photo_*.{jpg,png,webp}', GLOB_BRACE);
if ($existingPhotos === false) {
    error_log('Unable to list old employee profile photos for cleanup.');
} else {
    foreach ($existingPhotos as $existingPhoto) {
        if ($existingPhoto !== $photoPath && !unlink($existingPhoto)) {
            error_log('Unable to remove an old employee profile photo.');
        }
    }
}

$photoUrl = 'uploads/employee-profile/' . $photoKey . '/' . rawurlencode($photoName) . '?v=' . filemtime($photoPath);
profile_photo_response(200, ['ok' => true, 'message' => 'Profile photo updated.', 'url' => $photoUrl]);
