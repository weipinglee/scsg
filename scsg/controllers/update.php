<?php
/**
 * @brief 升级更新控制器
 */
class Update extends IController
{
	/**
	 * @brief iwebshop15071500 版本升级更新
	 */
	public function iwebshop15071500()
	{
		$sql = array(
			"CREATE TABLE `{pre}delivery_extend` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `delivery_id` int(11) unsigned NOT NULL COMMENT '配送方式关联ID',
			  `area_groupid` text COMMENT '单独配置地区id',
			  `firstprice` text COMMENT '单独配置地区对应的首重价格',
			  `secondprice` text COMMENT '单独配置地区对应的续重价格',
			  `first_weight` int(11) unsigned NOT NULL COMMENT '首重重量(克)',
			  `second_weight` int(11) unsigned NOT NULL COMMENT '续重重量(克)',
			  `first_price` decimal(15,2) NOT NULL default '0.00' COMMENT '默认首重价格',
			  `second_price` decimal(15,2) NOT NULL default '0.00' COMMENT '默认续重价格',
			  `is_save_price` tinyint(1) NOT NULL default '0' COMMENT '是否支持物流保价 1支持保价 0  不支持保价',
			  `save_rate` decimal(15,2) NOT NULL default '0.00' COMMENT '保价费率',
			  `low_price` decimal(15,2) NOT NULL default '0.00' COMMENT '最低保价',
			  `price_type` tinyint(1) NOT NULL default '0' COMMENT '费用类型 0统一设置 1指定地区费用',
			  `open_default` tinyint(1) NOT NULL default '1' COMMENT '启用默认费用 1启用 0 不启用',
			  `seller_id` int(11) unsigned NOT NULL COMMENT '商家ID',
			  PRIMARY KEY  (`id`),
			  index (`delivery_id`),
			  index (`seller_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='商家配送方式扩展表';",

			"ALTER TABLE `{pre}merch_ship_info` ADD `seller_id` int(11) unsigned  NOT NULL COMMENT '商家ID'",

			"ALTER TABLE `{pre}order_goods` ADD `delivery_fee` decimal(15,2) NOT NULL default '0.00' COMMENT '商品运费价格'",
			"ALTER TABLE `{pre}order_goods` ADD `save_price` decimal(15,2) NOT NULL default '0.00' COMMENT '商品保价'",
			"ALTER TABLE `{pre}order_goods` ADD `tax` decimal(15,2) NOT NULL default '0.00' COMMENT '商品税金'",

			"ALTER TABLE `{pre}seller` ADD `tax` decimal(15,2) NOT NULL default '0.00' COMMENT '税率'",
		);

		foreach($sql as $key => $val)
		{
			$val = str_replace('{pre}',IWeb::$app->config['DB']['tablePre'],$val);
			IDBFactory::getDB()->query($val);
		}

		die('升级成功！');
	}
}