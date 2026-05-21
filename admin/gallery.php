<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/lib/gallery.php';

$pdo = dbConnection();
$message = '';
$error = '';

$layouts = ['normal' => 'Normal', 'wide' => 'Wide (full row)', 'video' => 'Video'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'delete' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare('SELECT file_path, poster_path FROM gallery_items WHERE id = ?');
    $stmt->execute([(int) $_POST['id']]);
    $row = $stmt->fetch();
    if ($row) {
      gallery_delete_uploaded_file($row['file_path']);
      if (!empty($row['poster_path'])) {
        gallery_delete_uploaded_file($row['poster_path']);
      }
    }
    $stmt = $pdo->prepare('DELETE FROM gallery_items WHERE id = ?');
    $stmt->execute([(int) $_POST['id']]);
    $message = 'Gallery item deleted.';
  } elseif ($action === 'save') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $mediaType = $_POST['media_type'] ?? 'image';
    $filePath = trim((string) ($_POST['file_path'] ?? ''));
    $altText = trim((string) ($_POST['alt_text'] ?? ''));
    $caption = trim((string) ($_POST['caption'] ?? ''));
    $posterPath = trim((string) ($_POST['poster_path'] ?? ''));
    $layout = $_POST['layout'] ?? 'normal';
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (!in_array($mediaType, ['image', 'video'], true)) {
      $error = 'Invalid media type.';
    } elseif (!isset($layouts[$layout])) {
      $error = 'Invalid layout.';
    } elseif ($altText === '') {
      $error = 'Alt text / description is required.';
    } else {
      if (!empty($_FILES['media_file']['name'])) {
        $up = gallery_store_upload($_FILES['media_file'], $mediaType);
        if (!$up['ok']) {
          $error = $up['error'];
        } else {
          $filePath = $up['path'];
        }
      }
      if ($error === '' && !empty($_FILES['poster_file']['name'])) {
        $upPoster = gallery_store_upload($_FILES['poster_file'], 'image');
        if (!$upPoster['ok']) {
          $error = $upPoster['error'];
        } else {
          $posterPath = $upPoster['path'];
        }
      }
      if ($error === '' && $filePath === '' && $id > 0) {
        $stmt = $pdo->prepare('SELECT file_path FROM gallery_items WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetchColumn();
        $filePath = $existing ? (string) $existing : '';
      }
      if ($error === '' && $filePath === '') {
        $error = 'Upload a file or enter a file path.';
      } elseif ($error === '' && !gallery_is_valid_path($filePath)) {
        $error = 'Invalid file path. Use uploads/gallery/… or HGAY ASSETS/…';
      } elseif ($error === '' && $posterPath !== '' && !gallery_is_valid_path($posterPath)) {
        $error = 'Invalid poster path.';
      } elseif ($error === '') {
        if ($mediaType === 'image') {
          $layout = $layout === 'wide' ? 'wide' : 'normal';
          $caption = $caption !== '' ? $caption : null;
          $posterPath = null;
        } else {
          $layout = 'video';
        }
        if ($id > 0) {
          $old = $pdo->prepare('SELECT file_path, poster_path FROM gallery_items WHERE id = ?');
          $old->execute([$id]);
          $prev = $old->fetch();
          $stmt = $pdo->prepare('
            UPDATE gallery_items SET media_type=?, file_path=?, alt_text=?, caption=?, poster_path=?,
              layout=?, sort_order=?, is_active=? WHERE id=?
          ');
          $stmt->execute([
            $mediaType, $filePath, $altText,
            $caption !== '' ? $caption : null,
            $posterPath !== '' ? $posterPath : null,
            $layout, $sortOrder, $active, $id,
          ]);
          if ($prev && $prev['file_path'] !== $filePath) {
            gallery_delete_uploaded_file($prev['file_path']);
          }
          if ($prev && ($prev['poster_path'] ?? '') !== $posterPath && !empty($prev['poster_path'])) {
            gallery_delete_uploaded_file($prev['poster_path']);
          }
          $message = 'Gallery item updated.';
        } else {
          $stmt = $pdo->prepare('
            INSERT INTO gallery_items (media_type, file_path, alt_text, caption, poster_path, layout, sort_order, is_active)
            VALUES (?,?,?,?,?,?,?,?)
          ');
          $stmt->execute([
            $mediaType, $filePath, $altText,
            $caption !== '' ? $caption : null,
            $posterPath !== '' ? $posterPath : null,
            $layout, $sortOrder, $active,
          ]);
          $message = 'Gallery item added.';
        }
      }
    }
  }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'seeded') {
  $message = 'Seeded ' . (int) ($_GET['n'] ?? 0) . ' gallery items.';
}
if (isset($_GET['msg']) && $_GET['msg'] === 'already') {
  $message = 'Gallery already has items. Use force re-seed if needed.';
}
if (isset($_GET['err'])) {
  $error = (string) $_GET['err'];
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editItem = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM gallery_items WHERE id = ?');
  $stmt->execute([$editId]);
  $editItem = $stmt->fetch();
}

$items = [];
try {
  $items = $pdo->query('SELECT * FROM gallery_items ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (PDOException $e) {
  $error = $error ?: 'Gallery table missing. Run schema-update.sql on your database.';
}

$adminTitle = 'Gallery';
$adminPage = 'gallery';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Gallery</h1>
        <p>Manage images and videos on the <a href="<?php echo htmlspecialchars(site_url('gallery')); ?>" target="_blank" rel="noopener">public gallery</a> and home page preview.</p>
      </header>

      <?php if ($message): ?><div class="admin-alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="admin-card">
        <h2><?php echo $editItem ? 'Edit item' : 'Add item'; ?></h2>
        <form method="post" class="admin-form" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save">
          <?php if ($editItem): ?><input type="hidden" name="id" value="<?php echo (int) $editItem['id']; ?>"><?php endif; ?>
          <div class="form-row">
            <label for="media_type">Type</label>
            <select name="media_type" id="media_type" required>
              <option value="image"<?php echo ($editItem && $editItem['media_type'] === 'image') || !$editItem ? ' selected' : ''; ?>>Image</option>
              <option value="video"<?php echo ($editItem && $editItem['media_type'] === 'video') ? ' selected' : ''; ?>>Video</option>
            </select>
          </div>
          <div class="form-row">
            <label for="media_file">Upload file</label>
            <input type="file" name="media_file" id="media_file" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime">
            <p class="hint">JPEG, PNG, GIF, WebP (max 12 MB) or MP4/MOV (max 100 MB). Saved to uploads/gallery/</p>
          </div>
          <div class="form-row">
            <label for="file_path">Or existing path</label>
            <input type="text" name="file_path" id="file_path" placeholder="uploads/gallery/… or HGAY ASSETS/…" value="<?php echo $editItem ? htmlspecialchars($editItem['file_path']) : ''; ?>">
            <p class="hint">Leave upload empty when editing to keep the current file.</p>
          </div>
          <div class="form-row">
            <label for="alt_text">Alt text / description</label>
            <input type="text" name="alt_text" id="alt_text" required value="<?php echo $editItem ? htmlspecialchars($editItem['alt_text']) : ''; ?>">
          </div>
          <div class="form-row" id="caption-row">
            <label for="caption">Video label (optional)</label>
            <input type="text" name="caption" id="caption" placeholder="e.g. Launch Video" value="<?php echo $editItem ? htmlspecialchars($editItem['caption'] ?? '') : ''; ?>">
          </div>
          <div class="form-row" id="poster-row">
            <label for="poster_file">Video poster image (upload)</label>
            <input type="file" name="poster_file" id="poster_file" accept="image/jpeg,image/png,image/gif,image/webp">
          </div>
          <div class="form-row" id="poster-path-row">
            <label for="poster_path">Or poster path</label>
            <input type="text" name="poster_path" id="poster_path" value="<?php echo $editItem ? htmlspecialchars($editItem['poster_path'] ?? '') : ''; ?>">
          </div>
          <div class="form-row">
            <label for="layout">Layout</label>
            <select name="layout" id="layout">
              <?php foreach ($layouts as $key => $label): ?>
              <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($editItem && $editItem['layout'] === $key) ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="<?php echo $editItem ? (int) $editItem['sort_order'] : 0; ?>">
            <p class="hint">Lower numbers appear first.</p>
          </div>
          <div class="form-row">
            <label><input type="checkbox" name="is_active" value="1"<?php echo (!$editItem || $editItem['is_active']) ? ' checked' : ''; ?>> Published</label>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Update' : 'Add'; ?> item</button>
          <?php if ($editItem): ?><a href="gallery" class="btn btn-secondary" style="margin-left:8px">Cancel</a><?php endif; ?>
        </form>
      </div>

      <div class="admin-card">
        <div class="admin-filters">
          <a href="seed_gallery" class="btn btn-secondary" onclick="return confirm('Import default gallery from site assets?');">Seed from assets</a>
          <a href="seed_gallery?force=1" class="btn btn-secondary" onclick="return confirm('Delete ALL gallery items and re-seed?');">Re-seed (force)</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Preview</th><th>Type</th><th>Path</th><th>Order</th><th>Live</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <?php
                $thumb = '../' . $item['file_path'];
                $isVideo = $item['media_type'] === 'video';
              ?>
              <tr>
                <td>
                  <?php if ($isVideo): ?>
                  <span class="admin-gallery-thumb admin-gallery-thumb--video">Video</span>
                  <?php else: ?>
                  <img src="<?php echo htmlspecialchars($thumb); ?>" alt="" class="admin-gallery-thumb" loading="lazy">
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($item['media_type']); ?> / <?php echo htmlspecialchars($item['layout']); ?></td>
                <td style="max-width:220px;font-size:0.8rem;word-break:break-all"><?php echo htmlspecialchars($item['file_path']); ?></td>
                <td><?php echo (int) $item['sort_order']; ?></td>
                <td><?php echo $item['is_active'] ? 'Yes' : 'No'; ?></td>
                <td class="admin-actions">
                  <a href="gallery?edit=<?php echo (int) $item['id']; ?>" class="admin-btn-sm secondary">Edit</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this item?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                    <button type="submit" class="admin-btn-sm danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($items)): ?>
              <tr><td colspan="6">No items. <a href="seed_gallery">Seed from assets</a> or add one above.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <style>
        .admin-gallery-thumb { width: 56px; height: 42px; object-fit: cover; border-radius: 6px; display: block; }
        .admin-gallery-thumb--video { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 42px; background: var(--admin-surface-2); border-radius: 6px; font-size: 0.7rem; }
      </style>
<?php require_once 'includes/layout_end.php'; ?>
