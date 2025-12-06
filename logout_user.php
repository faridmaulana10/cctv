<?php
session_start();
session_unset();
session_destroy();
header("Location: login_user.php?logout=1");
exit;
?>