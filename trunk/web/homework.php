<?php
$uri = $_SERVER['REQUEST_URI'];
$uri = str_replace("homework.php", "contest.php?hw=1&", $uri);
header("Location: $uri");
