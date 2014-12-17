<?php
$aop_config = array(
	array(
        'event'	    => 'before', // 切入的类型
        'point'     => array('class'=>'Entry', 'method'=>'save'), // 切入的主业务类和方法 point的 方法支持 * 符(该类任何方法都会被切入)
        'advice'    => array('class'=>'Auth', 'method'=>'userLevel') // 支线任务类名，方法
    ),
	array(
        'event'	    => 'before',
        'point'     => array('class'=>'Entry', 'method'=>'save'),
        'advice'    => array('class'=>'Auth', 'method'=>'isBlackUser')
    ),
	
    array(
        'event'	    => 'around',
        'point'     => array('class'=>'Entry', 'method'=>'save'),
        'advice'    => array('class'=>'Auth', 'method'=>'checkLimit')
    ),
    array(
        'event'     => 'after',
        'point'     => array('class'=>'Entry', 'method'=>'save'),
        'advice'    => array('class'=>'Log', 'method'=>'stats'),
    ),
);

?>