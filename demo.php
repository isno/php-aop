<?php

/**
 说明: aop的更多方法，可以阅读aop.php代码了解
 如果有疑问可以mail <isno.cn@gmail.com> , 博客 http://www.isno.cn
*/


// 定义advice文件目录
define('ADVICE_PATH',  dirname(__FILE__) . DIRECTORY_SEPARATOR.'/advices/');

require 'config.php'; // AOP 配置
require 'aop.php'; // aop 库文件, aop内 global 配置数组


/**
 * 主业务类
*/
class Entry{

	function save($user_id = 1){
		echo "我是发表博文";
		return 'isno';
	}
}


$entry = Aop::getInstance('Entry'); // 初始化AOP

/**
 有的次业务会抛出异常，用try执行捕捉异常，
*/
try{
	$ret = $entry->save(10000);
	echo $ret;
}
catch(Exception $e)
{
	echo $e->getMessage();
}


?>
