<?php
// Logo Upload Handler for Y R K MAHA BAZAAR
// Handles logo uploads via the web interface

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload error occurred.';
        echo json_encode($response);
        exit;
    }

    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'File size must be less than 5MB.';
        echo json_encode($response);
        exit;
    }

    // Check file type
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        $response['message'] = 'Only PNG, JPG, and SVG files are allowed.';
        echo json_encode($response);
        exit;
    }

    // Create target directory if it doesn't exist
    $target_dir = 'assets/images/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Create backup directory
    $backup_dir = $target_dir . 'backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0777, true);
    }

    // Backup current logos
    $current_files = [
        $target_dir . 'yrk-logo-hero.svg',
        $target_dir . 'yrk-logo-hero.png',
        $target_dir . 'yrk-logo-hero.jpg'
    ];

    foreach ($current_files as $current_file) {
        if (file_exists($current_file)) {
            $extension = pathinfo($current_file, PATHINFO_EXTENSION);
            $backup_name = 'yrk-logo-hero-' . date('Y-m-d-H-i-s') . '.' . $extension;
            copy($current_file, $backup_dir . $backup_name);
        }
    }

    // Determine target filename based on uploaded file type
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $target_filename = 'yrk-logo-hero.' . $extension;
    $target_path = $target_dir . $target_filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Update the main index.php to use the new logo format
        $index_file = 'index.php';
        if (file_exists($index_file)) {
            $content = file_get_contents($index_file);

            // Update image src to use the new format
            if ($extension === 'svg') {
                $new_src = 'assets/images/yrk-logo-hero.svg';
            } else {
                $new_src = 'assets/images/yrk-logo-hero.' . $extension;
            }

            // Replace old logo references
            $content = str_replace('assets/images/yrk-logo-hero.svg', $new_src, $content);
            $content = str_replace('assets/images/yrk-logo-hero.png', $new_src, $content);
            $content = str_replace('assets/images/yrk-logo-hero.jpg', $new_src, $content);

            file_put_contents($index_file, $content);
        }

        $response['success'] = true;
        $response['message'] = '✅ Logo uploaded successfully! Your new YRK logo is now live with beautiful animations.';

        // Add file info
        $response['file_info'] = [
            'name' => $target_filename,
            'size' => round($file['size'] / 1024, 2) . ' KB',
            'type' => $extension,
            'backed_up' => count($current_files) > 0
        ];

    } else {
        $response['message'] = '❌ Failed to save the logo file. Please check permissions.';
    }
} else {
    $response['message'] = '❌ No file uploaded or invalid request.';
}

echo json_encode($response);
?>
