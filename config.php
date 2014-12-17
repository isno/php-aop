<?php
$aop_config = array(
	array(
        'event'	    => 'before', // ���������
        'point'     => array('class'=>'Entry', 'method'=>'save'), // �������ҵ����ͷ��� point�� ����֧�� * ��(�����κη������ᱻ����)
        'advice'    => array('class'=>'Auth', 'method'=>'userLevel') // ֧����������������
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