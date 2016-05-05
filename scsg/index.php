<?php
$iweb = dirname(__FILE__)."/lib/iweb.php";
$config = dirname(__FILE__)."/config/config.php";
require($iweb);
require dirname(__FILE__)."/lib/function.php";
IWeb::createWebApp($config)->run();

?>