<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

const CARD_REFERENCE_CATEGORY = 'card_references';

$pdo = dbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'delete' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare('DELETE FROM fact_cards WHERE id = ? AND category = ?');
    $stmt->execute([(int) $_POST['id'], CARD_REFERENCE_CATEGORY]);
    $message = 'Reference deleted.';
  } elseif ($action === 'save') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $term = trim($_POST['term'] ?? '');
    $def = trim($_POST['definition'] ?? '');
    $kw = trim($_POST['keywords'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($term === '' || $def === '') {
      $error = 'Term and definition are required.';
    } else {
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE fact_cards SET question=?, answer=?, keywords=?, is_active=? WHERE id=? AND category=?');
        $stmt->execute([$term, $def, $kw, $active, $id, CARD_REFERENCE_CATEGORY]);
        $message = 'Reference updated.';
      } else {
        $stmt = $pdo->prepare('INSERT INTO fact_cards (category, question, answer, keywords, is_active) VALUES (?,?,?,?,?)');
        $stmt->execute([CARD_REFERENCE_CATEGORY, $term, $def, $kw, $active]);
        $message = 'Reference added.';
      }
    }
  }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM fact_cards WHERE id = ? AND category = ?');
  $stmt->execute([$editId, CARD_REFERENCE_CATEGORY]);
  $editRow = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM fact_cards WHERE category = ? ORDER BY sort_order, id');
$stmt->execute([CARD_REFERENCE_CATEGORY]);
$rows = $stmt->fetchAll();

$adminTitle = 'Card references';
$adminPage = 'card-references';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Card references</h1>
        <p>Glossary terms used on game cards (e.g. MENTAL, DUMSOR). Shown at the bottom of the <a href="<?php echo htmlspecialchars(site_url('fact-check')); ?>" target="_blank">Fact Check page</a> — not part of the Q&amp;A flip cards.</p>
      </header>

      <?php if ($message): ?><div class="admin-alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="admin-card">
        <h2><?php echo $editRow ? 'Edit reference' : 'Add reference'; ?></h2>
        <form method="post" class="admin-form">
          <input type="hidden" name="action" value="save">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?php echo (int) $editRow['id']; ?>"><?php endif; ?>
          <div class="form-row">
            <label for="term">Term (as on the card)</label>
            <input type="text" name="term" id="term" required value="<?php echo $editRow ? htmlspecialchars($editRow['question']) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="definition">Definition</label>
            <textarea name="definition" id="definition" required><?php echo $editRow ? htmlspecialchars($editRow['answer']) : ''; ?></textarea>
          </div>
          <div class="form-row">
            <label for="keywords">Search keywords (optional)</label>
            <input type="text" name="keywords" id="keywords" value="<?php echo $editRow ? htmlspecialchars($editRow['keywords'] ?? '') : ''; ?>">
          </div>
          <div class="form-row">
            <label><input type="checkbox" name="is_active" value="1"<?php echo (!$editRow || $editRow['is_active']) ? ' checked' : ''; ?>> Show on Fact Check page</label>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo $editRow ? 'Update' : 'Add'; ?> reference</button>
          <?php if ($editRow): ?><a href="card-references" class="btn btn-secondary" style="margin-left:8px">Cancel</a><?php endif; ?>
        </form>
      </div>

      <div class="admin-card">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Term</th><th>Definition</th><th>Active</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($r['question']); ?></strong></td>
                <td style="max-width:360px"><?php echo htmlspecialchars(mb_substr($r['answer'], 0, 100)); ?><?php echo mb_strlen($r['answer']) > 100 ? '…' : ''; ?></td>
                <td><?php echo $r['is_active'] ? 'Yes' : 'No'; ?></td>
                <td class="admin-actions">
                  <a href="card-references?edit=<?php echo (int) $r['id']; ?>" class="admin-btn-sm secondary">Edit</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this reference?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>">
                    <button type="submit" class="admin-btn-sm danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
              <tr><td colspan="4">No references. <a href="seed_fact_cards">Seed from official guide</a> includes reference terms.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
