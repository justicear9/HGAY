<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

$categories = require dirname(__DIR__) . '/config/fact_categories.php';
$pdo = dbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'delete' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare('DELETE FROM fact_cards WHERE id = ?');
    $stmt->execute([(int) $_POST['id']]);
    $message = 'Card deleted.';
  } elseif ($action === 'save') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $cat = $_POST['category'] ?? '';
    $q = trim($_POST['question'] ?? '');
    $a = trim($_POST['answer'] ?? '');
    $kw = trim($_POST['keywords'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($q === '' || $a === '' || !isset($categories[$cat])) {
      $error = 'Category, question, and answer are required.';
    } else {
      if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE fact_cards SET category=?, question=?, answer=?, keywords=?, is_active=? WHERE id=?');
        $stmt->execute([$cat, $q, $a, $kw, $active, $id]);
        $message = 'Card updated.';
      } else {
        $stmt = $pdo->prepare('INSERT INTO fact_cards (category, question, answer, keywords, is_active) VALUES (?,?,?,?,?)');
        $stmt->execute([$cat, $q, $a, $kw, $active]);
        $message = 'Card added.';
      }
    }
  }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'seeded') {
  $message = 'Seeded ' . (int) ($_GET['n'] ?? 0) . ' cards from official guide.';
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editCard = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM fact_cards WHERE id = ?');
  $stmt->execute([$editId]);
  $editCard = $stmt->fetch();
}

$filterCat = isset($_GET['category']) ? $_GET['category'] : '';
$sql = "SELECT * FROM fact_cards WHERE category != 'card_references'";
$params = [];
if ($filterCat !== '' && isset($categories[$filterCat])) {
  $sql .= ' AND category = ?';
  $params[] = $filterCat;
}
$sql .= ' ORDER BY category, sort_order, id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$cards = $stmt->fetchAll();

$adminTitle = 'Fact Check cards';
$adminPage = 'fact-cards';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Fact Check cards</h1>
        <p>Fact Check questions and answers — visitors tap to flip and reveal. For game-card terms like MENTAL or DUMSOR, use <a href="card-references">Card references</a>. (<a href="<?php echo htmlspecialchars(site_url('fact-check')); ?>" target="_blank">view page</a>)</p>
      </header>

      <?php if ($message): ?><div class="admin-alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="admin-card">
        <h2><?php echo $editCard ? 'Edit card' : 'Add card'; ?></h2>
        <form method="post" class="admin-form">
          <input type="hidden" name="action" value="save">
          <?php if ($editCard): ?><input type="hidden" name="id" value="<?php echo (int) $editCard['id']; ?>"><?php endif; ?>
          <div class="form-row">
            <label for="category">Category</label>
            <select name="category" id="category" required>
              <?php foreach ($categories as $key => $label): ?>
              <option value="<?php echo htmlspecialchars($key); ?>"<?php echo ($editCard && $editCard['category'] === $key) ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-row">
            <label for="question">Question / card prompt</label>
            <input type="text" name="question" id="question" required value="<?php echo $editCard ? htmlspecialchars($editCard['question']) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="answer">Answer</label>
            <textarea name="answer" id="answer" required><?php echo $editCard ? htmlspecialchars($editCard['answer']) : ''; ?></textarea>
          </div>
          <div class="form-row">
            <label for="keywords">Search keywords (optional)</label>
            <input type="text" name="keywords" id="keywords" value="<?php echo $editCard ? htmlspecialchars($editCard['keywords'] ?? '') : ''; ?>">
            <p class="hint">Extra words to help search (e.g. flag, independence).</p>
          </div>
          <div class="form-row">
            <label><input type="checkbox" name="is_active" value="1"<?php echo (!$editCard || $editCard['is_active']) ? ' checked' : ''; ?>> Active on Fact Check page</label>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo $editCard ? 'Update' : 'Add'; ?> card</button>
          <?php if ($editCard): ?><a href="fact-cards" class="btn btn-secondary" style="margin-left:8px">Cancel</a><?php endif; ?>
        </form>
      </div>

      <div class="admin-card">
        <div class="admin-filters">
          <form method="get">
            <label for="fcat">Category</label>
            <select name="category" id="fcat" onchange="this.form.submit()">
              <option value="">All</option>
              <?php foreach ($categories as $key => $label): ?>
              <option value="<?php echo htmlspecialchars($key); ?>"<?php echo $filterCat === $key ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <a href="seed_fact_cards" class="btn btn-secondary" onclick="return confirm('Import official cards from guide? Use force only if re-seeding.');">Seed from guide</a>
          <a href="seed_fact_cards?force=1" class="btn btn-secondary" onclick="return confirm('Delete ALL cards and re-seed?');">Re-seed (force)</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Category</th><th>Question</th><th>Active</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($cards as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($categories[$c['category']] ?? $c['category']); ?></td>
                <td style="max-width:320px"><?php echo htmlspecialchars(mb_substr($c['question'], 0, 80)); ?><?php echo mb_strlen($c['question']) > 80 ? '…' : ''; ?></td>
                <td><?php echo $c['is_active'] ? 'Yes' : 'No'; ?></td>
                <td class="admin-actions">
                  <a href="fact-cards?edit=<?php echo (int) $c['id']; ?>" class="admin-btn-sm secondary">Edit</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this card?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                    <button type="submit" class="admin-btn-sm danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($cards)): ?>
              <tr><td colspan="4">No cards. <a href="seed_fact_cards">Seed from official guide</a>.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
