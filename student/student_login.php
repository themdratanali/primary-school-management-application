<?php
// Backwards compatibility: if someone visits the old URL,
// redirect them to the new, cleaner login.php
header('Location: login.php');
exit;