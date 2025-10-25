<?php
// YRK Logo to Icon Generator
// Creates favicon and PWA icons from uploaded logo

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'icons_created' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    $file = $_FILES['logo'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload error occurred.';
        echo json_encode($response);
        exit;
    }

    // Check file size (10MB max for processing)
    if ($file['size'] > 10 * 1024 * 1024) {
        $response['message'] = 'File size must be less than 10MB for icon generation.';
        echo json_encode($response);
        exit;
    }

    // Check file type
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        $response['message'] = 'Only PNG and JPG files are supported for icon generation.';
        echo json_encode($response);
        exit;
    }

    // Create target directories
    $icons_dir = 'assets/images/icons/';
    if (!file_exists($icons_dir)) {
        mkdir($icons_dir, 0777, true);
    }

    // Icon sizes to generate
    $icon_sizes = [
        16, 32, 57, 60, 72, 76, 96, 114, 120, 128,
        144, 152, 180, 192, 384, 512
    ];

    $icons_created = 0;

    try {
        // Create image from uploaded file
        if ($mime_type === 'image/png') {
            $source_image = imagecreatefrompng($file['tmp_name']);
        } else {
            $source_image = imagecreatefromjpeg($file['tmp_name']);
        }

        if (!$source_image) {
            $response['message'] = 'Failed to process image file.';
            echo json_encode($response);
            exit;
        }

        // Get original dimensions
        $original_width = imagesx($source_image);
        $original_height = imagesy($source_image);

        // Create square version (crop to square)
        $size = min($original_width, $original_height);
        $square_image = imagecreatetruecolor($size, $size);

        // Enable alpha channel for PNG
        imagealphablending($square_image, false);
        imagesavealpha($square_image, true);

        // Fill with transparent background
        $transparent = imagecolorallocatealpha($square_image, 0, 0, 0, 127);
        imagefill($square_image, 0, 0, $transparent);

        // Calculate crop position (center crop)
        $src_x = ($original_width - $size) / 2;
        $src_y = ($original_height - $size) / 2;

        // Copy and resize
        imagecopyresampled($square_image, $source_image, 0, 0, $src_x, $src_y, $size, $size, $size, $size);

        // Generate icons for each size
        foreach ($icon_sizes as $icon_size) {
            $icon = imagecreatetruecolor($icon_size, $icon_size);

            // Enable alpha channel
            imagealphablending($icon, false);
            imagesavealpha($icon, true);

            // Fill with transparent background
            $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
            imagefill($icon, 0, 0, $transparent);

            // Resize and copy
            imagecopyresampled($icon, $square_image, 0, 0, 0, 0, $icon_size, $icon_size, $size, $size);

            // Save as PNG
            $icon_path = $icons_dir . 'icon-' . $icon_size . 'x' . $icon_size . '.png';
            if (imagepng($icon, $icon_path, 9)) {
                $icons_created++;
            }

            imagedestroy($icon);
        }

        // Clean up
        imagedestroy($square_image);
        imagedestroy($source_image);

        // Also create the main logo
        $logo_sizes = [400, 800];
        foreach ($logo_sizes as $logo_size) {
            $logo = imagecreatetruecolor($logo_size, $logo_size);

            // Enable alpha channel
            imagealphablending($logo, false);
            imagesavealpha($logo, true);

            // Fill with transparent background
            $transparent = imagecolorallocatealpha($logo, 0, 0, 0, 127);
            imagefill($logo, 0, 0, $transparent);

            // Resize and copy
            imagecopyresampled($logo, $square_image, 0, 0, 0, 0, $logo_size, $logo_size, $size, $size);

            // Save main logo
            $logo_path = 'assets/images/yrk-logo-hero.png';
            if (imagepng($logo, $logo_path, 9)) {
                $response['main_logo'] = true;
            }

            imagedestroy($logo);
        }

        $response['success'] = true;
        $response['message'] = "Successfully generated $icons_created icons and updated logo! Your website will now have consistent branding across all devices.";
        $response['icons_created'] = $icons_created;

    } catch (Exception $e) {
        $response['message'] = 'Error generating icons: ' . $e->getMessage();
    }

} else {
    $response['message'] = 'No file uploaded or invalid request.';
}

echo json_encode($response);
?>
