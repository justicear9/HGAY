<?php
require_once dirname(__DIR__) . '/lib/paths.php';
require_once 'auth.php';
header('Location: ' . admin_url('dashboard'));
exit;
