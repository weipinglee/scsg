<?php
/**
 * @file inline_action.php
 * @brief 控制器内部action
 * @date 2010-12-17
 * @version 0.6
 */

/**
 * @class IInlineAction
 * @brief 控制器内部action
 */
class IInlineAction extends IAction
{
	/**
	 * @brief 内部action动作执行方法
	 */
	public function run()
	{
		$controller=$this->getController();
		$methodName=$this->getId();
		$controller->$methodName();
	}
}
