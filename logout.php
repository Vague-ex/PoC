<?php
require_once __DIR__ . '/config.php';
if (user()) log_action('logged out');
session_destroy();
header('Location: index.php');
