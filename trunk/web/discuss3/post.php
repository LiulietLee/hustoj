<?php
session_start();
require_once("oj-header.php");

if (!isset($_SESSION[$OJ_NAME . '_' . 'user_id'])) {
    require_once("oj-header.php");
    echo "<a href=loginpage.php>Please Login First</a>";
    require_once("../oj-footer.php");
    exit(0);
}

function goBack($message) {
    echo "<script language='javascript'>\n";
    echo "alert('$message');\n";
    echo "history.go(-1);\n";
    echo "</script>";
    exit(0);
}

function getVcodeWrongMsg() {
    global $MSG_VCODE_WRONG;
    return $MSG_VCODE_WRONG;
}

function getEscapeString($str) {
//    $list = ['script', 'src'];
//    foreach ($list as $item) {
//        $str = str_ireplace($item, 'k-onsaikou!', $str);
//    }
//    return $str;

    return htmlentities($str);
}

$content = getEscapeString($_POST['content']);
$title = getEscapeString($_POST['title']);

if (strlen($content) > 5000) {
    require_once("oj-header.php");
    echo "Your contents is too long!";
    require_once("../oj-footer.php");
    exit(0);
}

if (strlen($title) > 60) {
    require_once("oj-header.php");
    echo "Your title is too long!";
    require_once("../oj-footer.php");
    exit(0);
}

$vcode = "";
if (isset($_POST['vcode'])) $vcode = trim($_POST['vcode']);
if ($OJ_VCODE && ($vcode != $_SESSION[$OJ_NAME . '_' . "vcode"] || $vcode == "" || $vcode == null)) {
    goBack(getVcodeWrongMsg());
}

$tid = null;
if ($_REQUEST['action'] == 'new') {
    if (array_key_exists('title', $_POST) && array_key_exists('content', $_POST) && $title != '' && $content != '') {
        if (array_key_exists('pid', $_REQUEST) && $_REQUEST['pid'] != '')
            $pid = intval($_REQUEST['pid']);
        else
            $pid = 0;
        if (array_key_exists('cid', $_REQUEST) && $_REQUEST['cid'] != '')
            $cid = intval($_REQUEST['cid']);
        else
            $cid = 0;

        $pidWrongMsg = "问题标号只能填四位数字哦";
        $numLen = strlen($pid);
        if ($numLen == 4) {
            $pattern = "/\d\d\d\d/";
            $valid = preg_match($pattern, $pid);
            if (!$valid) goBack($pidWrongMsg);
        } else if ($pid != 0) {
            goBack($pidWrongMsg);
        }

        if ($pid == 0) {
            if ($cid > 0) {
                $problem_id = htmlentities($_POST['pid'], ENT_QUOTES, 'UTF-8');
                $num = strpos($PID, $problem_id);
                $pid = pdo_query("select problem_id from contest_problem where contest_id=? and num=?", $cid, $num)[0][0];
            }

        }
        $sql = "INSERT INTO `topic` (`title`, `author_id`, `cid`, `pid`) values(?,?,?,?)";

        $rows = pdo_query($sql, $title, $_SESSION[$OJ_NAME . '_' . 'user_id'], $cid, $pid);
        if (!$rows) {
            //echo $sql;
            echo('Unable to post new.');
        } else {
            $tid = $rows;
        }
    } else
        echo('Error!');
}

if ($_REQUEST['action'] == 'reply' || !is_null($tid)) {
    if (is_null($tid)) $tid = intval($_POST['tid']);
    if (!is_null($tid) && array_key_exists('content', $_POST) && $content != '') {
        $rows = pdo_query("select tid from topic where tid=?", $tid);
        if (isset($rows[0])) {
            $ip = ($_SERVER['REMOTE_ADDR']);
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $REMOTE_ADDR = $_SERVER['HTTP_X_FORWARDED_FOR'];
                $tmp_ip = explode(',', $REMOTE_ADDR);
                $ip = (htmlentities($tmp_ip[0], ENT_QUOTES, "UTF-8"));
            }
            $sql = "insert INTO `reply` (`author_id`, `time`, `content`, `topic_id`,`ip`) values(?,NOW(),?,?,?)";
            if (pdo_query($sql, $_SESSION[$OJ_NAME . '_' . 'user_id'], $content, $tid, $ip)) {
                if (isset($_REQUEST['cid'])) {
                    $cid = intval($_REQUEST['cid']);
                    header('Location: thread.php?cid=' . $cid . '&tid=' . $tid);
                } else {
                    header('Location: thread.php?tid=' . $tid);
                }
                exit(0);
            } else {
                echo('Unable to post.');
            }
        } else {
            echo "reply non-exists topic";
        }
    } else echo('Error!');
}

require_once("../oj-footer.php");
?>
