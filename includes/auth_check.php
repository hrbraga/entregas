<?php
// auth_check.php (Vamos incluir isto em todas as páginas seguras)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>