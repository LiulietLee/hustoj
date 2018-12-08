<?php
////////////////////////////Common head
$cache_time = 30;
$OJ_CACHE_SHARE = true;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
$view_title = "Recent Contests from Naikai-contest-spider";

$view_news = "";
$sql = "select * "
    . "FROM `news` "
    . "WHERE `defunct`!='Y' AND `news_id`=1005 "
    . "ORDER BY `importance` ASC,`time` DESC "
    . "LIMIT 1";
$result = mysql_query_cache($sql);//mysql_escape_string($sql));
if (!$result) {
    $view_news = "<h3>No News Now!</h3>";
} else {
    $view_news .= "<table width=96%>";

    foreach ($result as $row) {
        $view_news .= "<tr><td><td>" . $row['content'] . "</tr>";
    }
    $view_news .= "</table>";
}


/////////////////////////Template
require("template/" . $OJ_TEMPLATE . "/recent-contest.php");
/////////////////////////Common foot
if (file_exists('./include/cache_end.php'))
    require_once('./include/cache_end.php');
?>



