<?php return array (
  'logs' => 
  array (
    'path' => 'backup/logs/log',
    'type' => 'file',
  ),
  'DB' => 
  array (
    'type' => 'mysqli',
    'tablePre' => 'shop_',
    'read' => 
    array (
      0 => 
      array (
        'host' => 'localhost:3306',
        'user' => 'root',
        'passwd' => '127890',
        'name' => 'shop',
      ),
    ),
    'write' => 
    array (
      'host' => 'localhost:3306',
      'user' => 'root',
      'passwd' => '127890',
      'name' => 'shop',
    ),
  ),
  'interceptor' => 
  array (
    0 => 'themeroute@onCreateController',
    1 => 'layoutroute@onCreateView',
    2 => 'hookCreateAction@onCreateAction',
    3 => 'hookFinishAction@onFinishAction',
  ),
  'langPath' => 'language',
  'viewPath' => 'views',
  'skinPath' => 'skin',
  'classes' => 'classes.*',
  'rewriteRule' => 'pathinfo',
  'theme' => 
  array (
	'pc' => 'scsg',
    'mobile' => 'mobile',

  ),
  'skin' => 
  array (
    'pc' => 'black',
    'mobile' => 'black',
  ),
  'timezone' => 'Etc/GMT-8',
  'upload' => 'upload',
  'dbbackup' => 'backup/database',
  'safe' => 'session',
  'lang' => 'zh_sc',
  'debug' => true,
  'configExt' => 
  array (
    'site_config' => 'config/site_config.php',
  ),
  'encryptKey' => 'b01cb56e21fe2810a987aadcd74828dc',
)?>