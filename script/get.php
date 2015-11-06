<?php
header("Content-Type: text/html;charset=utf-8");
$cookie = $_POST["Cookie"];
$redis = new Redis();

$redis->connect('127.0.0.1', 6379);

if (strpos($cookie, "DedeUserID") === FALSE or strpos($cookie, "DedeUserID__ckMd5") === FALSE or strpos($cookie, "SESSDATA") === FALSE) {
	echo "<script>alert('Cookie数据不符合要求，请重试！');history.go(-1);</script>";
	exit;
}
$keyname = preg_replace('{(.*)?DedeUserID=([\d]+);(.*)?}', '$2', $cookie);

if ($redis->exists($keyname)) {
	$status = $redis->hget($keyname, 'status');
	$cookieexist = $redis->hget($keyname, 'cookie');
	if ($status == 'processing') {
		echo "<script>alert('正在为你领取银瓜子~');history.go(-1);</script>";
		exit;
	}
	elseif ($status == 'processed') {
		echo "<script>alert('今天的银瓜子已经领完了，明天再来吧~');history.go(-1);</script>";
		exit;
	}
	elseif ($status == 'problem') {
		if ($cookieexist == $cookie) {
			echo "<script>alert('Cookie数据有问题，请尝试重新登录获取后再提交！');history.go(-1);</script>";
			exit;
		}
		else {
			echo "<script>alert('成功更新Cookie数据！');</script>";
		}
	}
	else {
		echo "<script>alert('你的任务正在队列中，稍后再看看吧~');history.go(-1);</script>";
		exit;
	}
}

$outtime = strtotime("tomorrow") - time();

if ($outtime <= 3600) {
	echo "<script>alert('今天太晚了，明天再来吧~');history.go(-1);</script>";
	exit;
}

$redis->hset($keyname, 'cookie', $cookie);
$redis->hset($keyname, 'status', 'queuing');
$redis->expire($keyname, $outtime);

echo "<script>alert('你的任务已经加入队列中，下一个小时开始领取！');history.go(-1);</script>";
exit;

?>
