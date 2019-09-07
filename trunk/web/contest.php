 <?php
if(isset($_POST['keyword']))
  $cache_time = 1;
else
  $cache_time = 30;

$OJ_CACHE_SHARE = false;//!(isset($_GET['cid'])||isset($_GET['my']));
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/my_func.inc.php');
require_once('./include/const.inc.php');
require_once('./include/setlang.php');
$view_title= $MSG_CONTEST;

$homeworkCheck = (isset($_GET['hw'])) ? intval($_GET['hw']) : 0;
$hwQuery = (isset($homeworkCheck) && $homeworkCheck == 1) ? "hw=1&" : "";

if(isset($_GET['cid'])){
  $cid = intval($_GET['cid']);
  $view_cid = $cid;
  //print $cid;

  //check contest valid
  $sql = "SELECT * FROM `contest` WHERE `contest_id`=? AND `homework`=?";
  $result = pdo_query($sql,$cid, $homeworkCheck);

  $rows_cnt = count($result);
  $contest_ok = true;
  $password = "";

  if(isset($_POST['password'])) $password = $_POST['password'];
  if(get_magic_quotes_gpc()){ $password = stripslashes($password);}

  if($rows_cnt==0){
    $view_title = "比赛已经关闭!";
    header("Location: /contest.php?$hwQuery");
    exit();
  }else{
    $row = $result[0];
    $view_private = $row['private'];

    if($password!=""&&$password==$row['password']) $_SESSION[$OJ_NAME.'_'.'c'.$cid] = true;
    if($row['private'] && !isset($_SESSION[$OJ_NAME.'_'.'c'.$cid])) $contest_ok = false;
    if($row['defunct']=='Y') $contest_ok = false;
    if(isset($_SESSION[$OJ_NAME.'_'.'administrator'])) $contest_ok = true;

    $now = time();
    $start_time = strtotime($row['start_time']);
    $end_time = strtotime($row['end_time']);
    $view_title = $row['title'];

    if(!isset($_SESSION[$OJ_NAME.'_'.'administrator']) && $now<$start_time){
      $view_errors =  "<h2>$MSG_PRIVATE_WARNING</h2>";
      require("template/".$OJ_TEMPLATE."/error.php");
      exit(0);
    }
  }

  if(!$contest_ok){
    $view_errors =  "<h2>$MSG_PRIVATE_WARNING <br><a href=contestrank.php?". $hwQuery. "cid=$cid>$MSG_WATCH_RANK</a></h2>";
    $view_errors .=  "<form method=post action='contest.php?". $hwQuery. "cid=$cid'>$MSG_CONTEST $MSG_PASSWORD:<input class=input-mini type=password name=password><input class=btn type=submit></form>";
    require("template/".$OJ_TEMPLATE."/error.php");
    exit(0);
  }

  $sql = "SELECT * FROM (SELECT `problem`.`title` AS `title`,`problem`.`problem_id` AS `pid`,source AS source, contest_problem.num as pnum FROM `contest_problem`,`problem` WHERE `contest_problem`.`problem_id`=`problem`.`problem_id` AND `contest_problem`.`contest_id`=? ORDER BY `contest_problem`.`num`) problem LEFT JOIN (SELECT problem_id pid1,count(distinct(user_id)) accepted FROM solution WHERE result=4 AND contest_id=? GROUP BY pid1) p1 ON problem.pid=p1.pid1 LEFT JOIN (SELECT problem_id pid2,count(1) submit FROM solution WHERE contest_id=? GROUP BY pid2) p2 ON problem.pid=p2.pid2 ORDER BY pnum";//AND `problem`.`defunct`='N'

  $result = pdo_query($sql,$cid,$cid,$cid);
  $view_problemset = Array();

  $cnt = 0;

  foreach($result as $row){
    $view_problemset[$cnt][0] = "";
    if(isset($_SESSION[$OJ_NAME.'_'.'user_id'])) $view_problemset[$cnt][0] = check_ac($cid,$cnt);

    $view_problemset[$cnt][1] = $row['pid']." Problem &nbsp;".$PID[$cnt];
    $view_problemset[$cnt][2] = "<a href='problem.php?". $hwQuery. "cid=$cid&pid=$cnt'>".$row['title']."</a>";
    $view_problemset[$cnt][3] = $row['source'];
    $view_problemset[$cnt][4] = $row['accepted'];
    $view_problemset[$cnt][5] = $row['submit'] ;
    $cnt++;
  }
}else{
  $page = 1;
  if(isset($_GET['page'])) $page = intval($_GET['page']);
  $page_cnt = 10;
  $pstart = $page_cnt*$page-$page_cnt;
  $pend = $page_cnt;
  $rows = pdo_query("select count(1) from contest where defunct='N' and homework=?", $homeworkCheck);

  if($rows) $total = $rows[0][0];
  $view_total_page = intval($total/$page_cnt)+1;
  $keyword = "";

  if(isset($_POST['keyword'])){ $keyword="%".$_POST['keyword']."%";}
  //echo "$keyword";

  $mycontests = "";
  $len = mb_strlen($OJ_NAME.'_');

  foreach($_SESSION as $key => $value){
    if(($key[$len]=='m'||$key[$len]=='c')&&intval(mb_substr($key,$len+1))>0){
      //echo substr($key,1)."<br>";
      $mycontests.=",".intval(mb_substr($key,$len+1));
    }
  }

  if(strlen($mycontests)>0) $mycontests=substr($mycontests,1);
  //echo "$mycontests";
 
  $wheremy = "";
  if(isset($_GET['my'])) $wheremy=" and contest_id in ($mycontests)";

  $sql = "SELECT * FROM `contest` WHERE `defunct`='N' AND `homework`=? ORDER BY `contest_id` DESC LIMIT 1000";

  if($keyword){
    $sql = "SELECT *  FROM contest LEFT JOIN (SELECT * FROM privilege WHERE rightstr LIKE 'm%') p ON concat('m',contest_id)=rightstr WHERE contest.defunct='N' AND homework=? AND contest.title LIKE ? $wheremy  ORDER BY contest_id DESC";
	
	$sql .= " limit ".strval($pstart).",".strval($pend); 

	$result = pdo_query($sql, $homeworkCheck, $keyword);
  }else{
    $sql = "SELECT *  FROM contest LEFT JOIN (SELECT * FROM privilege WHERE rightstr LIKE 'm%') p ON concat('m',contest_id)=rightstr WHERE contest.defunct='N' AND homework=$homeworkCheck $wheremy  ORDER BY contest_id DESC";
	$sql .= " limit ".strval($pstart).",".strval($pend); 
	$result = mysql_query_cache($sql);
  }
  
  $view_contest = Array();
  $i = 0;

  foreach($result as $row){
    $view_contest[$i][0] = $row['contest_id'];
    $view_contest[$i][1] = "<a href='contest.php?". $hwQuery. "cid=".$row['contest_id']."'>".$row['title']."</a>";
    $start_time = strtotime($row['start_time']);
    $end_time = strtotime($row['end_time']);
    $now = time();
                                
    $length = $end_time-$start_time;
    $left = $end_time-$now;
	//past

    if($now>$end_time){
      $view_contest[$i][2] = "<span class=green>$MSG_Ended@".$row['end_time']."</span>";
      //pending

    }else if ($now<$start_time){
  	  $view_contest[$i][2] = "<span class=blue>$MSG_Start@".$row['start_time']."</span>&nbsp;";
      $view_contest[$i][2] .= "<span class=green>$MSG_TotalTime".formatTimeLength($length)."</span>";
	  //running
    }else{
  	  $view_contest[$i][2] = "<span class=red> $MSG_Running</font>&nbsp;";
      $view_contest[$i][2] .= "<span class=green> $MSG_LeftTime ".formatTimeLength($left)." </span>";
    }

    $private = intval($row['private']);
    if($private==0) $view_contest[$i][4] = "<span class=blue>$MSG_Public</span>";
    else $view_contest[$i][5] = "<span class=red>$MSG_Private</span>";

    $view_contest[$i][6]=$row['user_id'];

    $i++;
  }
}

/////////////////////////Template
if(isset($_GET['cid'])) require("template/".$OJ_TEMPLATE."/contest.php");
else require("template/".$OJ_TEMPLATE."/contestset.php");
/////////////////////////Common foot
if(file_exists('./include/cache_end.php')) require_once('./include/cache_end.php');
?>
