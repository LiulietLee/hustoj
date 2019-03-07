<?php

require_once("admin-header.php");

$userID = $_GET['user_id'];
if (get_magic_quotes_gpc()) {
    $userID = stripslashes($userID);
}
$readingAuth = max(0, min(intval($_GET['reading_authority']), 10000));

if (!isset($_SESSION[$OJ_NAME . '_' . "m$userID"]) &&
    !isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {
    exit();
}

$sql = "UPDATE users SET reading_authority=? WHERE user_id=?";
pdo_query($sql, $readingAuth, $userID);

?>

<script language=javascript>
    history.go(-1);
</script>

