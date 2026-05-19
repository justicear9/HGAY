<?php
/**
 * Public events calendar JSON.
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
require_once dirname(__DIR__) . '/config/database.php';

try {
  $pdo = dbConnection();
  $stmt = $pdo->query("
    SELECT id, title, description, location, event_date, event_time,
           price_display, registration_url, registration_label
    FROM events
    WHERE is_active = 1
    ORDER BY event_date ASC, event_time ASC, sort_order ASC, id ASC
  ");
  $events = [];
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $events[] = [
      'id' => (int) $row['id'],
      'title' => $row['title'],
      'description' => $row['description'],
      'location' => $row['location'],
      'event_date' => $row['event_date'],
      'event_time' => $row['event_time'] ? substr($row['event_time'], 0, 5) : null,
      'price_display' => $row['price_display'] ?? '',
      'registration_url' => $row['registration_url'] ?? '',
      'registration_label' => $row['registration_label'] ?: 'Register',
    ];
  }
  echo json_encode(['events' => $events, 'count' => count($events)]);
} catch (Throwable $e) {
  error_log('events_list: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['events' => [], 'count' => 0, 'error' => true]);
}
