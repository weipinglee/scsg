<?php
/**
 * @file sellermenu.php
 * @brief 商家后台系统菜单管理
 * @author 
 * @date 2016-7-14 15:45:13
 * @version 0.1
 * @note
 */
class SellerMenu
{
	private static $commonMenu = array('/seller/index');
    private $seller = '';//管理员
	private $sellerRights = '';//管理员的权限

	/**
	 * @brief 构造函数
	 * @param array checkrights里面的admin对象数据
	 */
	public function __construct($seller)
	{
		$sellerObj = new IModel('seller');
		$sellerRow = $sellerObj->getObj('seller_name = "'.$seller['seller_name'].'" and is_lock = 0');
		if($sellerRow && (isset($seller['seller_pwd']) && $sellerRow['password'] == $seller['seller_pwd']) && ($sellerRow['is_del'] == 0))
		{
            //根据角色分配权限
            $this->adminRights = 'administrator';
		}
        else
        {
            $adminsellerObj = new IQuery('admin_seller as a');
            $adminsellerObj->join = 'join seller as s on a.seller_id = s.id';
            $adminsellerObj->where = 'admin_name = "'.$seller['seller_name'].'" and a.is_del = 0 and s.is_del = 0 and s.is_lock = 0';
            $adminsellerObj->fields = 'a.*';
            $sellerRow = $adminsellerObj->getObj();
            if($sellerRow['role_id'] == 0)
            {
                $this->adminRights = 'administrator';
            }
            else
            {
                $roleObj = new IModel('admin_role');
                $where   = 'id = '.$sellerRow["role_id"].' and is_del = 0';
                $roleRow = $roleObj->getObj($where);
                $this->adminRights = isset($roleRow['rights']) ? $roleRow['rights'] : '';
            }
        }
        $this->seller = $seller;
	}
    //菜单的配制数据
	private static $menu = array(
		'统计结算模块'=>array(
            '/seller/index' => '管理首页&icn_tags',
            '/seller/account' => '销售额统计&icn_settings',
			'/seller/order_goods_list' => '货款明细列表&icn_pc_list',
			'/seller/bill_list' => '货款结算申请&icn_photo'
		),
        '权限管理'=>array(
            '/seller/seller_list' => '管理员&icn_pc_list',
            '/seller/role_list' => '角色&icn_pc_list',
            '/seller/right_list' => '权限资源&icn_pc_list'
        ),
		'商品模块'=>array(
			'/seller/goods_list'	=>	'商品列表&icon_pc_sp',
            '/seller/goods_edit'    =>    '添加商品&icn_new_article',
            '/seller/combine_list'    =>    '组合列表&icon_pc_sp',
            '/seller/combine_edit'    =>    '添加组合&icn_new_article',
            '/seller/goods_in'    =>    '库存添加&icn_pc_kc',
            '/seller/share_list'    =>    '平台共享商品&icon_pc_gx',
            '/seller/refer_list'    =>    '商品咨询&icon_pc_comment',
            '/seller/comment_list'    =>    '商品评价&icn_pc_edit',
			'/seller/spec_list'	=>	'规格列表&icn_categories',
		),
		'售后服务'=>array(
            '/seller/refundment_list'  =>    '退货申请&icon_pc_th',
            '/seller/refundment_chg_list'  =>    '换货申请&icon_pc_hh',
            '/seller/refundment_list_handled'  =>    '退货单&icon_pc_thd',
            '/seller/refundment_chg_list_handled'  =>    '换货单&icon_pc_hhd',
            '/seller/fapiao_apply'  =>    '发票申请单&icon_pc_bfpdl',
			'/seller/fapiao_list'  =>	'已开发票列表&icon_pc_bkfp'
		),
		'订单模块'=>array(
			'/seller/order_list'=>'订单列表&icon_pc_dd'
		),
		'营销模块'=>array(
			'/seller/pro_rule_list'=>'促销活动列表&icn_view_users',
			'/seller/shan_list'=>'闪购&icn_view_users',
			'/seller/regiment_list'=>'团购&icon_pc_tg'
		),
        '代金券管理'=>array(
            '/seller/ticket_list'       => '代金券列表&icon_pc_bkfp',
            '/seller/ticket_excel_list' => '代金券文件列表&icn_pc_list',
        ),
		'配置模块'=>array(
            '/seller/delivery' => '物流配送&icon_pc_wl',
            '/seller/ship_info_list' => '发货地址&icon_pc_fhdz',
            '/seller/seller_edit' => '资料修改&icn_profile',
            /*'/seller/chg_pass' => '密码修改&icon_pc_key',*/
			'/site/home/id/@seller_id@' => '主页信息&icn_video',
		)
	);

    /**
     * @brief 根据用户的权限过滤菜单
     * @return array
     */
    private function filterMenu()
    {
    	$rights = $this->adminRights;

		//如果不是超级管理员则要过滤菜单
		if($rights != 'administrator')
		{
			foreach(self::$menu as $firstKey => $firstVal)
			{
				if(is_array($firstVal))
				{
					foreach($firstVal as $secondKey => $secondVal)
					{
						if(!in_array($secondKey,self::$commonMenu) && (stripos(str_replace('@','/',$rights),','.substr($secondKey,1).',') === false))
                        {
                            unset(self::$menu[$firstKey][$secondKey]);
                        }
					}
					if(empty(self::$menu[$firstKey]))
					{
						unset(self::$menu[$firstKey]);
					}
				}
			}
		}
    }

    /**
     * @brief 取得当前菜单应该生成的对应JSON数据
     * @param boolean $is_auto 是否智能匹配菜单
     * @return Json
     */
	public function submenu($is_auto = false)
	{
		$result     = array();
		$controller = IWeb::$app->getController()->getId();
		$action     = IWeb::$app->getController()->getAction()->getId();

		//过滤无操作权限的菜单
		$this->filterMenu();

		foreach(self::$menu as $key => $value)
		{
			$item = array();
			$item['title']   = $key;
			foreach($value as $k => $v)
			{
                if(stripos($k, '@seller_id@'))
                {
                    $k = str_replace('@seller_id@', $this->seller['seller_id'], $k);
                }
                $tem = explode('&', $v);
                $class = isset($tem[1]) ? $tem[1] : '';
                $item['list'][] = array('title' => $tem[0], 'class' => $class, 'link' => IUrl::creatUrl($k));
			}
			$result[] = $item;
		}

		if($is_auto == false)
		{
			return $this->submenu(true);
		}
		return JSON::encode($result);
	}
}