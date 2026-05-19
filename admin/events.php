<?php
require_once 'auth.php';
require_once dirname(__DIR__) . '/config/database.php';

$pdo = dbConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];
  if ($action === 'delete' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
    $stmt->execute([(int) $_POST['id']]);
    $message = 'Event deleted.';
  } elseif ($action === 'save') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $eventDate = trim((string) ($_POST['event_date'] ?? ''));
    $eventTime = trim((string) ($_POST['event_time'] ?? ''));
    $priceDisplay = trim((string) ($_POST['price_display'] ?? ''));
    $regUrl = trim((string) ($_POST['registration_url'] ?? ''));
    $regLabel = trim((string) ($_POST['registration_label'] ?? 'Register'));
    $active = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($title === '' || $description === '' || $location === '' || $eventDate === '') {
      $error = 'Title, description, location, and date are required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
      $error = 'Invalid event date.';
    } elseif ($eventTime !== '' && !preg_match('/^\d{2}:\d{2}$/', $eventTime)) {
      $error = 'Time must be HH:MM (24-hour).';
    } elseif ($regUrl !== '' && !filter_var($regUrl, FILTER_VALIDATE_URL)) {
      $error = 'Registration link must be a valid URL (include https://).';
    } else {
      $timeVal = $eventTime !== '' ? $eventTime . ':00' : null;
      $regLabel = $regLabel !== '' ? $regLabel : 'Register';
      if ($id > 0) {
        $stmt = $pdo->prepare('
          UPDATE events SET title=?, description=?, location=?, event_date=?, event_time=?,
            price_display=?, registration_url=?, registration_label=?, is_active=?, sort_order=?
          WHERE id=?
        ');
        $stmt->execute([
          $title, $description, $location, $eventDate, $timeVal,
          $priceDisplay, $regUrl !== '' ? $regUrl : null, $regLabel, $active, $sortOrder, $id,
        ]);
        $message = 'Event updated.';
      } else {
        $stmt = $pdo->prepare('
          INSERT INTO events (title, description, location, event_date, event_time,
            price_display, registration_url, registration_label, is_active, sort_order)
          VALUES (?,?,?,?,?,?,?,?,?,?)
        ');
        $stmt->execute([
          $title, $description, $location, $eventDate, $timeVal,
          $priceDisplay, $regUrl !== '' ? $regUrl : null, $regLabel, $active, $sortOrder,
        ]);
        $message = 'Event added.';
      }
    }
  }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editEvent = null;
if ($editId > 0) {
  $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
  $stmt->execute([$editId]);
  $editEvent = $stmt->fetch();
}

$events = [];
try {
  $events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC, event_time ASC, sort_order ASC, id DESC')->fetchAll();
} catch (PDOException $e) {
  $error = $error ?: 'Events table missing. Run schema-update.sql on your database.';
}

$adminTitle = 'Events';
$adminPage = 'events';
require_once 'includes/layout_start.php';
?>
      <header class="admin-header">
        <h1>Events</h1>
        <p>Create and manage events on the public <a href="<?php echo htmlspecialchars(site_url('events')); ?>" target="_blank" rel="noopener">Events calendar</a>.</p>
      </header>

      <?php if ($message): ?><div class="admin-alert success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="admin-alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <div class="admin-card">
        <h2><?php echo $editEvent ? 'Edit event' : 'Add event'; ?></h2>
        <form method="post" class="admin-form">
          <input type="hidden" name="action" value="save">
          <?php if ($editEvent): ?><input type="hidden" name="id" value="<?php echo (int) $editEvent['id']; ?>"><?php endif; ?>
          <div class="form-row">
            <label for="title">Event title</label>
            <input type="text" name="title" id="title" required value="<?php echo $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="event_date">Date</label>
            <input type="date" name="event_date" id="event_date" required value="<?php echo $editEvent ? htmlspecialchars($editEvent['event_date']) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="event_time">Time (optional)</label>
            <input type="time" name="event_time" id="event_time" value="<?php echo $editEvent && $editEvent['event_time'] ? htmlspecialchars(substr($editEvent['event_time'], 0, 5)) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="location">Location</label>
            <input type="text" name="location" id="location" required placeholder="Venue, city" value="<?php echo $editEvent ? htmlspecialchars($editEvent['location']) : ''; ?>">
          </div>
          <div class="form-row">
            <label for="description">Description</label>
            <textarea name="description" id="description" required rows="5"><?php echo $editEvent ? htmlspecialchars($editEvent['description']) : ''; ?></textarea>
          </div>
          <div class="form-row">
            <label for="price_display">Price</label>
            <input type="text" name="price_display" id="price_display" placeholder="e.g. Free, 50 GHS" value="<?php echo $editEvent ? htmlspecialchars($editEvent['price_display'] ?? '') : ''; ?>">
            <p class="hint">Shown on the event card. Leave blank if not applicable.</p>
          </div>
          <div class="form-row">
            <label for="registration_url">Registration link</label>
            <input type="url" name="registration_url" id="registration_url" placeholder="https://…" value="<?php echo $editEvent ? htmlspecialchars($editEvent['registration_url'] ?? '') : ''; ?>">
            <p class="hint">Optional. Shows as a button on the public Events page.</p>
          </div>
          <div class="form-row">
            <label for="registration_label">Button label</label>
            <input type="text" name="registration_label" id="registration_label" value="<?php echo $editEvent ? htmlspecialchars($editEvent['registration_label'] ?? 'Register') : 'Register'; ?>">
          </div>
          <div class="form-row">
            <label for="sort_order">Sort order</label>
            <input type="number" name="sort_order" id="sort_order" value="<?php echo $editEvent ? (int) $editEvent['sort_order'] : 0; ?>">
            <p class="hint">Lower numbers appear first on the same day.</p>
          </div>
          <div class="form-row">
            <label><input type="checkbox" name="is_active" value="1"<?php echo (!$editEvent || $editEvent['is_active']) ? ' checked' : ''; ?>> Published on Events page</label>
          </div>
          <button type="submit" class="btn btn-primary"><?php echo $editEvent ? 'Update' : 'Add'; ?> event</button>
          <?php if ($editEvent): ?><a href="events" class="btn btn-secondary" style="margin-left:8px">Cancel</a><?php endif; ?>
        </form>
      </div>

      <div class="admin-card">
        <h2>All events</h2>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Date</th><th>Title</th><th>Location</th><th>Price</th><th>Live</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($events as $ev): ?>
              <tr>
                <td><?php echo htmlspecialchars($ev['event_date']); ?><?php if ($ev['event_time']): ?><br><small><?php echo htmlspecialchars(substr($ev['event_time'], 0, 5)); ?></small><?php endif; ?></td>
                <td style="max-width:200px"><?php echo htmlspecialchars($ev['title']); ?></td>
                <td style="max-width:160px"><?php echo htmlspecialchars(mb_substr($ev['location'], 0, 40)); ?><?php echo mb_strlen($ev['location']) > 40 ? '…' : ''; ?></td>
                <td><?php echo $ev['price_display'] !== '' && $ev['price_display'] !== null ? htmlspecialchars($ev['price_display']) : '—'; ?></td>
                <td><?php echo $ev['is_active'] ? 'Yes' : 'No'; ?></td>
                <td class="admin-actions">
                  <a href="events?edit=<?php echo (int) $ev['id']; ?>" class="admin-btn-sm secondary">Edit</a>
                  <form method="post" style="display:inline" onsubmit="return confirm('Delete this event?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $ev['id']; ?>">
                    <button type="submit" class="admin-btn-sm danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($events)): ?>
              <tr><td colspan="6">No events yet. Add one above.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
<?php require_once 'includes/layout_end.php'; ?>
