<?php
/**
 * @brief 消息模块
 * @class Message
 * @note  后台
 */
class Message extends IController
{
	public $checkRight = 'all';
	public $layout     = 'admin';
	private $data      = array();

	function init()
	{
		IInterceptor::reg('CheckRights@onCreateAction');
	}

	//删除电子邮箱订阅
	function registry_del()
	{
		$ids = IFilter::act(IReq::get('id'),'int');
		if(!$ids)
		{
			$this->redirect('registry_list',false);
			Util::showMessage('请选择要删除的邮箱');
			exit;
		}

		if(is_array($ids))
		{
			$ids = join(',',$ids);
		}

		$registryObj = new IModel('email_registry');
		$registryObj->del('id in ('.$ids.')');
		$this->redirect('registry_list');
	}

	/**
	 * @brief 删除登记的到货通知邮件
	 */
	function notify_del()
	{
		$notify_ids = IFilter::act(IReq::get('check'),'int');
		if($notify_ids)
		{
			$ids = join(',',$notify_ids);
			$tb_notify = new IModel('notify_registry');
			$where = "id in (".$ids.")";
			$tb_notify->del($where);
		}
		$this->redirect('notify_list');
	}

	/**
	 * @brief 发送到货通知邮件
	 */
	function notify_send()
	{
		$smtp  = new SendMail();
		$error = $smtp->getError();

		if($error)
		{
			$return = array(
				'isError' => true,
				'message' => $error,
			);
			echo JSON::encode($return);
			exit;
		}

		$notify_ids = IFilter::act(IReq::get('notifyid'));
		$message    = '';
		if($notify_ids && is_array($notify_ids))
		{
			$ids = join(',',$notify_ids);
			$query = new IQuery("notify_registry as notify");
			$query->join   = "right join goods as goods on notify.goods_id=goods.id left join user as u on notify.user_id = u.id";
			$query->fields = "notify.*,u.username,goods.name as goods_name,goods.store_nums";
			$query->where  = "notify.id in(".$ids.")";
			$items = $query->find();

			//库存大于0，且处于未发送状态的 发送通知
			$succeed = 0;
			$failed  = 0;
			$tb_notify_registry = new IModel('notify_registry');

			foreach($items as $value)
			{
				$body   = mailTemplate::notify(array('{goodsName}' => $value['goods_name'],'{url}' => IUrl::getHost().IUrl::creatUrl('/site/products/id/'.$value['goods_id'])));
				$status = $smtp->send($value['email'],"到货通知",$body);

				if($status)
				{
					//发送成功
					$succeed++;
					$data = array('notify_time' => ITime::getDateTime(),'notify_status' => '1');
					$tb_notify_registry->setData($data);
					$tb_notify_registry->update('id='.$value['id']);
				}
				else
				{
					//发送失败
					$failed++;
				}
			}
		}

		$return = array(
			'isError' => false,
			'count'   => count($items),
			'succeed' => $succeed,
			'failed'  => $failed,
		);
		echo JSON::encode($return);
	}

	/**
	 * @brief 发送信件
	 */
	function registry_message_send()
	{
		set_time_limit(0);
		$ids     = IFilter::act(IReq::get('ids'),'int');
		$title   = IFilter::act(IReq::get('title'));
		$content = IReq::get("content");

		$smtp  = new SendMail();
		$error = $smtp->getError();

		$list = array();
		$tb   = new IModel("email_registry");

		$ids_sql = "1";
		if($ids)
		{
			$ids_sql = "id IN ({$ids})";
		}

		$start = 0;
		$query = new IQuery("email_registry");
		$query->fields = "email";
		$query->order  = "id DESC";
		$query->where  = $ids_sql;

		do
		{
			$query->limit = "{$start},50";
			$list = $query->find();
			if(!$list)
			{
				die('没有要发送的邮箱数据');
				break;
			}
			$start += 51;

			$to = array_pop($list);
			$to = $to['email'];
			$bcc = array();
			foreach($list as $value)
			{
				$bcc[] = $value['email'];
			}
			$bcc = join(";",$bcc);
			$result = $smtp->send($to,$title,$content,$bcc);
			if(!$result)
			{
				die('发送失败');
			}
		}
		while(count($list)>=50);
		echo "success";
	}
}

