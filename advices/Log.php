<?php
/***
 业务统计类
**/
class Log{
	function stats(AdviceContainer $container){
		echo "#log统计#";
		$ret = $container->getRet(); // 得到主业务的返回值 after切入方式的才可获取到

		echo $ret;
	}
}


?>