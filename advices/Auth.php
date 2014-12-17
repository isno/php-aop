<?php
/**
  权限
*/
class Auth{
	/**
	  检测用户等级
	*/
	function userLevel(AdviceContainer $container){
		$container->setAdviceResult("user_status", 100); // 设置advice通信变量，可以在另外的切面中用 getAdviceResult("user_status"); 获取
		/*
		$user = false;
		if(!$user){
			// throw new Exception('用户等级太小，不能发微博'); 
			// userLevel的切入类型是before，所以主业务不会被执行
		}
		*/
	}

	/*
	 查看用户是否被屏蔽
	*/
	public function isBlackUser(AdviceContainer $container){

		if(!$container->issetAdviceResult("user_status")) {
			throw new LazyAdviceException(); // 延迟执行

			// 即在 Auth->userLevel 方法之后执行 (userLevel方法初始化了user_status值)

			// $ret = $container->getAdviceResult("user_status");
		}

		$user_id = $container->getParam("user_id"); // 得到主业务的参数

		//echo $user_id;
		
		//$container->setParam("user_id", 20000); // 重设主业务的参数, 不建议使用

		
		$user = false;
		if(!$user){
			// throw new Exception('用户被屏蔽'); 
			// 在切面可以直接抛出异常退出

		}
		
		$user = $container->getAdviceResult('user_status'); // 用于切面通信

		echo $user;
	}

	public function checkLimit(AdviceContainer $container){
		$user = $container->getAdviceResult('user_status');
		$ret = $container->proceed(); // around 环绕切入要返回业务的返回值 # 执行主业务方法
		// 
		return $ret;
	}
}

?>