<?php
/**
 * Gallery upload and path helpers.
 */

/** Map legacy folder names to current HGAY_ASSETS layout. */
function gallery_normalize_asset_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return $path;
    }

    $replacements = [
        'HGAY ASSETS/' => 'HGAY_ASSETS/',
        'Card Pictures and Video/' => 'Card_Pictures_and_Video/',
        'white bg/' => 'white_bg/',
    ];
    foreach ($replacements as $from => $to) {
        $path = str_replace($from, $to, $path);
    }

    return $path;
}

function gallery_upload_dir(): string
{
    return dirname(__DIR__) . '/uploads/gallery';
}

function gallery_is_valid_path(string $path): bool
{
    $path = trim($path);
    if ($path === '' || str_contains($path, '..')) {
        return false;
    }
    return (bool) preg_match('#^(uploads/gallery/|HGAY_ASSETS/).+#u', $path);
}

function gallery_resolve_path(string $path): ?string
{
    $path = gallery_normalize_asset_path($path);
    if (!gallery_is_valid_path($path)) {
        return null;
    }
    $full = dirname(__DIR__) . '/' . $path;
    return is_file($full) ? $full : null;
}

/**
 * @return array{ok: bool, path?: string, error?: string}
 */
function gallery_store_upload(array $file, string $mediaType): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload failed or no file selected.'];
    }

    $maxBytes = $mediaType === 'video' ? 100 * 1024 * 1024 : 12 * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => $mediaType === 'video' ? 'Video must be under 100 MB.' : 'Image must be under 12 MB.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';

    $imageMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $videoMimes = ['video/mp4' => 'mp4', 'video/quicktime' => 'mov'];

    if ($mediaType === 'image') {
        if (!isset($imageMimes[$mime])) {
            return ['ok' => false, 'error' => 'Image must be JPEG, PNG, GIF, or WebP.'];
        }
        $ext = $imageMimes[$mime];
    } else {
        if (!isset($videoMimes[$mime])) {
            return ['ok' => false, 'error' => 'Video must be MP4 or MOV.'];
        }
        $ext = $videoMimes[$mime];
    }

    $dir = gallery_upload_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return ['ok' => false, 'error' => 'Could not create upload folder.'];
    }

    $name = 'gallery-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => 'Could not save uploaded file.'];
    }

    return ['ok' => true, 'path' => 'uploads/gallery/' . $name];
}

function gallery_delete_uploaded_file(string $path): void
{
    if (str_starts_with($path, 'uploads/gallery/')) {
        $full = dirname(__DIR__) . '/' . $path;
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
