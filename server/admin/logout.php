<?php
session_name("ADMINSESS");
session_start();
$_SESSION["admin"] = "";
session_destroy();
header("Location: /admin/");
?>
