<?php
require_once __DIR__ . '/../includes/functions.php';
destroy_current_session();
session_start();
flash('success', 'Logout effettuato.');
redirect(APP_BASE_URL . '/index.php');
