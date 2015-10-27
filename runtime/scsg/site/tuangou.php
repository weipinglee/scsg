<?php 
	$myCartObj  = new Cart();
	$myCartInfo = $myCartObj->getMyCart();
	$siteConfig = new Config("site_config");
	$callback   = IReq::get('callback') ? urlencode(IFilter::act(IReq::get('callback'),'url')) : '';
	$categoryTop = Api::run('getCategoryListTop',10);
?>
<?php if($this->user){?>
<?php $m = new IModel('favorite');
$favorite_id_arr = $m->getFields(array('user_id'=>$this->user['user_id']),'rid');
$favorite_ids = implode(',',$favorite_id_arr);?>
<?php }?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<head>	
	<title><?php echo $siteConfig->name;?> </title>
	<link type="image/x-icon" href="http://www.#.com/site/favicon.ico" rel="icon">
	<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/public.css";?>">
	<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index.css";?>">
	<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/style.css";?>">
	<LINK rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/css.css";?>">
	<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/PyoBeside.css";?>"/>
	<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index1.css";?>">
<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/PyoBeside.js";?>"></script>
	<meta name="renderer" content="webkit">
	<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,uer-scalabe=no"/>
	<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/jquery/jquery-1.11.3.min.js"></script><script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/jquery/jquery-migrate-1.2.1.min.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/form/form.js"></script>
	<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/autovalidate/validate.js"></script><link rel="stylesheet" type="text/css" href="/xinde/scsg/runtime/_systemjs/autovalidate/style.css" />
	<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/artTemplate/artTemplate.js"></script><script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/artTemplate/artTemplate-plugin.js"></script>
	<script type='text/javascript' src='<?php echo IUrl::creatUrl("/lib/web/js/source/artdialog/artDialog.js?skin=default");?>' ></script>
		<script type='text/javascript' src='<?php echo IUrl::creatUrl("/lib/web/js/source/artdialog/plugins/iframeTools.js");?>' ></script>
	<script type='text/javascript' src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/common.js";?>"></script>
	<script type='text/javascript' src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/site.js";?>'></script>
	<script type="text/javascript"  src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/nav.js";?>"></script>
	<meta name="keywords" content="<?php echo $siteConfig->index_seo_keywords;?>">
	<meta name="description" content="<?php echo $siteConfig->index_seo_description;?>">

	
</head>
<body>
<script type='text/javascript'>
	$(function(){
		$('#user_log,.hd_user_center').hover(function(){$('.hd_user_center').show();},function(){$('.hd_user_center').hide();})
	})
</script>
<div style="background:#F15A24;">
	<div class="top"><div id="toTop"></div>
		<!--导航开始-->
		<div class="nav_z site-nav">

			<ul  class="cl sn-login" style="float:left;" id='user_log'>
				<li style="width:502px;color:#fff;">
					Hi,<?php if($this->user){?>
					<?php echo isset($this->user['show'])?$this->user['show']:"";?>
						欢迎您来到<?php echo $siteConfig->name;?>！
					<?php }else{?>
						欢迎您来到<?php echo $siteConfig->name;?>！
					<a href="<?php echo IUrl::creatUrl("/simple/login?callback=".$callback."");?>">登录</a>
					<span class="split"></span>
					<a href="<?php echo IUrl::creatUrl("/simple/reg?callback=".$callback."");?>">免费注册</a>
					<?php }?>
				</li>
			</ul>
			<?php if($this->user){?>
				<div class="hd_user_center hide" >
					<a href="<?php echo IUrl::creatUrl("/simple/logout");?>" class="blue_link">退出登录</a>
					<div class="clearfix">
						<div class="fl">
							<a class="hd_avata_box" href="<?php echo IUrl::creatUrl("/ucenter/");?>"><img src="<?php echo IUrl::creatUrl("".$this->user['head_ico']."");?>" style='width:62px;' onerror='this.src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/user_ico.gif";?>"'/></a>
							<a class="grzl" href="<?php echo IUrl::creatUrl("/ucenter/");?>">个人资料</a>
						</div>
						<div class="yhname">
							<p><a class="name"><?php echo isset($this->user['show'])?$this->user['show']:"";?></a><a class="hydj"><!--<b style="font-size:20px">V</b>0会员--></a></p>
							<p><span>会员等级:</span><a class="hd_login" href=""><?php echo isset($this->user['group_name'])?$this->user['group_name']:"";?></a></p>
							<div class="hy_line">
								<p class="hy_line_bar"></p>
							</div>
						</div>
					</div>
					<div class="hy_message">
						<a  href="">
							<b><?php echo isset($this->user['point'])?$this->user['point']:"";?></b>
							<span>积分<em><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/qiandao.gif";?>"/></em></span>
							
						</a>
						<a  href="">
							<b><?php echo isset($this->user['balance'])?$this->user['balance']:"";?></b>
							<span>账户余额</span>
							<span></span>
						</a>
						<a  href="">
							<b><?php echo isset($this->user['exp'])?$this->user['exp']:"";?></b>
							<span>成长值</span>
							<span></span>
						</a>
					</div>
				</div>
				<?php }?>
			<ul id="navul" class="cl" style="float:right;margin-left:170px;">
				<li>
					<a href="<?php echo IUrl::creatUrl("/ucenter/index");?>">个人中心</a>
					<ul>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/order");?>">我的订单</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/address");?>">我的收货地址</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/integral");?>">我的积分</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/account_log");?>">我的资金</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/favorite");?>">我的关注</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/history");?>">我的足迹</a></li>
					</ul>
				</li>
				<li>
					<a href="<?php echo IUrl::creatUrl("/simple/seller");?>">申请开店</a>
				</li>
				<li>
					<a href="#">掌上山城</a>
					<ul>
						<li style="height:160px;width:140px;border:0;box-shadow: 0 1px 3px #ccc;"><a href="#"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/erweima1.png";?>"></a></li>
					</ul>
				</li>
				<li>
					<a href="<?php echo IUrl::creatUrl("/site/help_list");?>">帮助中心</a>
					<ul>
						<li><a href="<?php echo IUrl::creatUrl("/site/help_list/id/9");?>">常见问题</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/site/help_list/id/4");?>">支付帮助</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/site/help/id/61");?>">当天送达</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/site/help/id/56");?>">积分说明</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/site/help/id/63");?>">退换货说明</a></li>
					</ul>
				</li>
				<li>
					<a href="<?php echo IUrl::creatUrl("/ucenter/index");?>">客户服务</a>
					<ul>
						<li><a href="javascript:showService()">在线客服</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/complain");?>">意见建议</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/site/help_list/id/5");?>">售后服务</a></li>
						<li><a href="<?php echo IUrl::creatUrl("/ucenter/order");?>">订单状态</a></li>
					</ul>
				</li>
				<li style="color:#fff;">关注我们:
				</li>
				<li  class="erwei">
					<a href="#" class="erwei" title="关注山城速购新浪微博"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/weixin1_01.png";?>"></a>
					<ul>
						<li style="height:160px;width:140px;border:0;box-shadow: 0 1px 3px #ccc;"><a href="#"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/erweima1.png";?>"></a></li>
					</ul>
				</li>
				<li class="erwei">
					<a href="#" class="erwei" title="关注山城速购微信"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/weixin1_02.png";?>"></a>
					<ul>
						<li style="height:160px;width:140px;border:0;box-shadow: 0 1px 3px #ccc;"><a href="#"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/erweima1.png";?>"></a></li>
					</ul>
				</li>
				<!--可在此处直接添加导航-->
			</ul>
		</div><!--导航结束-->
		<script  type="text/javascript"> 
		$(".navbg").capacityFixed();
		</script>

	</div>

<!--导航-->
</div>	

<?php if($this->isIndex){?>
<!--下拉广告图-->

<DIV class="advbox" >
	<DIV style="display: none;" class="dt_small">
		<DIV style="display: none;" class="dt_toBig"></DIV>
		<?php $query = new IQuery("ad_manage as ad");$query->fields = "ad.name,ad.link,ad.content";$query->where = "position_id = 20";$items = $query->find(); foreach($items as $key => $adData){?>
			
		<?php }?>
			<A href="<?php echo isset($adData['name'])?$adData['name']:"";?>" target="_blank"><IMG alt="jquery广告图片缩略图" src="<?php echo IUrl::creatUrl("".$adData['content']."");?>" width="1190" ></A> 
	</DIV>
	<DIV class="dt_big">
		<DIV class="dt_toSmall"></DIV>
		<?php $query = new IQuery("ad_manage as ad");$query->fields = "ad.name,ad.link,ad.content";$query->where = "position_id = 19";$items = $query->find(); foreach($items as $key => $adBig){?>
			
		<?php }?>
		<A href="<?php echo isset($adBig['name'])?$adBig['name']:"";?>" target="_blank">
			<IMG id="actionimg" alt="jquery广告图片大图" src="<?php echo IUrl::creatUrl("".$adBig['content']."");?>" width="1190" ></A> 
	</DIV>
</DIV>
<SCRIPT type="text/javascript">
	var searchUrl = '<?php echo IUrl::creatUrl("/site/search_list/word/");?>';
function AdvClick(){
	var a=1500;
	var b=3*1000;
	$(".dt_toSmall").click(function(){
		$(".dt_small").delay(a).slideDown(a);
		$(".dt_big").stop().slideUp(a);
		$(".dt_toSmall").stop().fadeOut(0);
		$(".dt_toBig").delay(a*2).fadeIn(0)
	});$
	(".dt_toBig").click(function(){
		$(".dt_big").delay(a).slideDown(a);
		$(".dt_small").stop().slideUp(a);
		$(".dt_toBig").stop().fadeOut(0);
		$(".dt_toSmall").delay(a*2).fadeIn(0)
	})
}

function AdvAuto(){
	if($(".dt_big").length>0){
		var a=1000;
		var b=3*500;
		$(".dt_big").delay(b).slideUp(a,function(){
			$(".dt_small").slideDown(a);
			$(".dt_toBig").delay(a).fadeIn(0)
		});
		$(".dt_toSmall").delay(b).fadeOut(0)
	}
}
</SCRIPT>

<SCRIPT type="text/javascript">
$(document).ready(function(){
	AdvClick();
});
//顶部通览可展开收起效果
if($("#actionimg").length>0){	
	$("#actionimg").onload=AdvAuto();
}
</SCRIPT>

<!--下拉广告图-->
<?php }?>
<!--search-->
<div class="search">
	<!--w1190 clearfix-->
	<div class="w1190 clearfix">
		<!--logo-->
		<div class="logo">
			<a href="<?php echo IUrl::creatUrl("/");?>" class="go-home">
				<?php if(isset($this->logoUrl)){?>
				<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/$this->logoUrl";?>" alt="" class="png">
				<?php }else{?>
				<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/logo.png";?>" alt="" class="png">
				<?php }?>
			</a>
		</div>
		<!--logo-->

		<!--cart f-r-->
		<div class="cart f-r mycart" id="jCart">
			<span class="cart-count"><span name="mycart_count"><?php echo isset($myCartInfo['count'])?$myCartInfo['count']:"";?></span></span>
			<a href="<?php echo IUrl::creatUrl("/simple/cart");?>" class="cart-buying"><span class="cart-account">去购物车结算</span></a>
			
		</div>
		
		<!--cart f-r-->

		<!--s-box f-r-->
		<div class="s-box f-r">
			<!--s-main-->
			<form method="get" action="<?php echo IUrl::creatUrl("/");?>">
			<input type="hidden" name="controller" value="site">
			<input type="hidden" name="action" value="search_list">
			<div class="s-main">
				<input class="ipt-key" type="text" name="word" onkeyup="getKeywords('<?php echo IUrl::creatUrl("/block/getLikeWords");?>',$(this))" autocomplete="off" value="输入关键字...">
				<input class="btn-search" type="submit" style='cursor:pointer;' value="搜索" onclick="checkInput(&#39;word&#39;,&#39;输入关键字...&#39;);">
				
			</div>
			</form>
			<div class='words-give' >
				
			</div>
			<!--s-main-->
			<!--kw-suggest-->
			<div class="kw-suggest">
				<?php foreach(Api::run('getKeywordList') as $key => $item){?>
				<a href="<?php echo IUrl::creatUrl("/site/search_list/word/".$item['word']."");?>"><?php echo isset($item['word'])?$item['word']:"";?></a>
				<?php }?>
			</div>
			<!--kw-suggest-->
		</div>
		<!--s-box f-r-->

	</div>
	<!--w1190 clearfix-->
</div>
<!--search-->

<!--nav-->
<div class="nav">
	<!--w1190 nav-main-->
	<div class="w1190 nav-main">
		<!--nav-cat-pos-->
		<div class="nav-cat-pos">
			<!--category-->
			<div class="category " id="jCat">
				<h2 class="cat-title"><a class="t14" href="">全部商品分类<b class="png"></b></a></h2>
				
				<ul class="cat-list" <?php if(!isset($this->isIndex)){?>style='display:none;'<?php }?>>
					<?php $hotCat=array();?>
					<?php foreach($categoryTop as $keyT => $valT){?>
					
					<?php $k=$keyT+1;?>
					<li class="list-item" style='position:static;'>
						<h3 class="cat-type-<?php echo isset($k)?$k:"";?>">
							<a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$valT['id']."");?>" class='underline-a'><?php echo isset($valT['name'])?$valT['name']:"";?></a>
						</h3>
						<p class="cat-rcmd">
							<?php $secondCat=Api::run('getCategoryByParentid',array('#parent_id#',$valT['id']))?>
							<?php foreach($secondCat as $keyS => $valS){?>
								<a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$valS['id']."");?>" class='underline-a'><?php echo isset($valS['name'])?$valS['name']:"";?></a>
							<?php }?>
						</p>
						<div class="cat-pop" style="display:none;top:0px;">
			                    <!--cat-layer-->
								<div class="cat-layer clearfix ">
			                        <ul class="cat-col">
			                        	<?php $hotCat[$valT['id']]=array();?>
			                        	<?php foreach($secondCat as $keyS => $valS){?>
										<?php if($valS['hot']==1 && count($hotCat[$valT['id']])<=5){?>
										<?php $hotCat[$valT['id']][] = $valS;?>
										<?php }?>
					                     <li class="cat-item">
					                   		 <h3 class="level-title"><a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$valS['id']."");?>" class='underline-a'><?php echo isset($valS['name'])?$valS['name']:"";?></a></h3>
					                   		 <p class="level-list clearfix">
					                   		 	<?php foreach(Api::run('getCategoryByParentid',array('#parent_id#',$valS['id']),30) as $keyG => $valG){?>
													<?php if($valG['hot']==1 && count($hotCat[$valT['id']])<=5){?>
														<?php $hotCat[$valT['id']][] = $valG;?>
													<?php }?>
													<a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$valG['id']."");?>" class='underline-a'><?php echo isset($valG['name'])?$valG['name']:"";?></a>
												<?php }?>
										     </p>
					                    </li>
										<?php if(($keyS+1)%3==0){?>
											<div class='clear'></div>
										<?php }?>
									<?php }?>
			                         </ul>
								</div>
								<!--cat-layer-->
								
					
	                    </div>
					</li>
					<?php }?>
				
					<div  id='nav-ad' style="float:right;display:none;"><?php echo Ad::show("导航右侧");?></div>
				</ul>
				<!--cat-list-->
	
		  </div>
			<!--category-->
		</div>
		<!--nav-cat-pos-->

		<!--nav-cnt-->
		<div class="nav-cnt">
			<?php foreach(Api::run('getGuideList') as $key => $item){?>
						<li class="nav-item"><a href="<?php echo IUrl::creatUrl("".$item['link']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a></li>
			<?php }?>			
		</div>
		<!--nav-cnt-->

	</div>
	<!--w1190 nav-main-->
</div>


<?php if(!isset($this->isIndex)){?>
<?php if($this->yushou){?>
	<div class="bn" id='shan_ad'>
			<?php echo Ad::show("预售top");?>
	</div>
	<script type=text/javascript >$('#shan_ad').find('img').css('width','100%');</script>
<?php }?>
<?php if($this->tuangou){?>
	<div class="bn" id='shan_ad'>
			<?php echo Ad::show("团购top");?>
	</div>
	<script type=text/javascript >$('#shan_ad').find('img').css('width','100%');</script>
<?php }?>
<div style='width:1190px;margin:5px auto;'>
	<link href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/tm.css";?>" rel="stylesheet">
<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/shan/shan.js";?>"></script>
<div class="cnt w1190">
<style type="text/css">
	h1{font-family:"微软雅黑";font-size:20px;margin-top:20px;letter-spacing:2px;}

	.time-item strong{background:#C71C60;color:#fff;line-height:49px;font-size:18px;font-family:Arial;padding:0 5px;margin-right:5px;}
	.time-item a:hover{color:#c71c60}
	#day_show{float:left;line-height:49px;color:#c71c60;font-size:14px;margin:0 5px;font-family:Arial, Helvetica, sans-serif;}
	.item-title .unit{background:none;line-height:49px;font-size:12px;padding:0 5px;float:left;}
	
	.pp_tm_show li{
		position: relative;
		float: left;
		width: 435px;
		height: 300px;
		margin: 20px 10px 0 0;
		padding: 10px;
		border: 1px solid #ccc;
		background: #fff;overflow:hidden;
		font-size:16px;
		
	}
	.pp_tm_show li:hover {
		background: #f15a24;
		border: 1px solid #f15a24;
	}
	.pp_tm_show dd{margin-top:15px;}

	.pp_tm_show .pl{float:left;}
	.pp_tm_show .pr{float:right;}

</style>

<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/base.css";?>">
<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index_sg.css";?>">
<!--tuan-->
<link type="text/css" rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/resource.css";?>">
<!--tuan-->
<!-- 团购活动列表 -->


<div class="pp_tm_con_fr">
	<a name="floor-1"></a>
		<h3>今日团购<i>TOP</i></h3>
</div>
<div class="mb10 warp-all group-list">
	<input name='start' type='hidden' value='<?php echo isset($this->count)?$this->count:"";?>'/>
	  <div class="left_group">
	  	<?php foreach($this->todayList as $key => $item){?>
		<?php $end=strtotime($item['end_time'])?>
		<?php $zhe = 10*round($item['regiment_price']/$item['sell_price'],2)?>
	        <div class="today-deals-con">
		        <div class="deal-img-wrap">
		            <a title="" href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" target="_blank"><img src="<?php echo IUrl::creatUrl("".$item['img']."");?>" alt=""  width="350" height="350"></a>
		            <div class="all_bicon_box"></div>
		        </div>
		        <div class="deal-summary"> 
				<span class="bk-timeleft home_timer today_end_time countdown" id="timeRemaining-<?php echo isset($key)?$key:"";?>">
					<span class="icon">距团购结束</span>
					<span id='cd_day_<?php echo isset($key)?$key:"";?>'></span>天
					<span id='cd_hour_<?php echo isset($key)?$key:"";?>'></span>小时
					<span id='cd_minute_<?php echo isset($key)?$key:"";?>'></span>分
					<span id='cd_second_<?php echo isset($key)?$key:"";?>'></span>秒	
					<input name='endTime' type='hidden' value='<?php echo isset($end)?$end:"";?>' />
				</span>
		            <a class="deal-summary-link" target="_blank" href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>}">
		                <p class="tit"> <em><?php echo isset($zhe)?$zhe:"";?>折/</em><?php echo isset($item['intro'])?$item['intro']:"";?></p>
		                <p class="price-wrap">
		                    <span class="price"><em>¥</em><?php echo isset($item['regiment_price'])?$item['regiment_price']:"";?></span>
		                    <span class="dis"><del>¥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></del></span> 
		                </p>
		                <div class="other clearfix">
		                  <span class="buy_num_con"><span class="buy_number"><?php echo isset($item['sum_count'])?$item['sum_count']:"";?></span>人已经购买</span>
		                </div>
		                <p class="summary-btn"> <span class="deal-btn">去看看</span>
		                </p>
		            </a>
		        </div>
	   		 </div>
		<?php }?> 
	</div>
    <div class="right_group">
		<a href="" target="_blank">
			<img id="pic_adv_297" style="width:300px;height:333px" border="0" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/tuan1.png";?>" alt="团购右侧1">
		</a>   
		 <a href="" target="_blank">
		 	<img id="pic_adv_298" style="width:300px;height:333px" border="0" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/tuan2.jpg";?>" alt="">
		 </a>  
	</div>
</div>

<div class="clear"></div>

<style>
	.shan-brand li {width:358px;height:280px;}
	.shan-brand li dt img { max-width:358px;max-height:220px; }
	.shan-brand .brand-img {width:358px;height:220px;}
	.pp_tm_con_se{width:1190px;}
	.pp_tm_con_se_l{width:1190px;}
	.width { width:1200px;margin:0 auto;}
</style>
<?php if(isset($this->brandList)&&$this->brandList){?>
<div class="pp_tm_con">
		  <div class="pp_tm_con_fr"><a name="floor-2"></a>
			<h3>品牌团<i>Brand</i></h3>
		  </div>
			<div class="pp_tm_con_se">
				<div class="pp_tm_con_se_l">
				  <!--品牌特卖 -->
					<div class="shan-brand width">
						<ul class="shan-all-li">
							<?php foreach($this->brandList as $key => $item){?>
							<?php $end=strtotime($item['end_time']);$k=$key+2;?>
								<li style="margin-top:0;">
									<dl>
										<dt class="brand-img">
											<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" target="_blank" title="<?php echo isset($item['title'])?$item['title']:"";?>">
												<img class="lazyload img"  src="<?php echo IUrl::creatUrl("".$item['img']."");?>" alt="<?php echo isset($item['title'])?$item['title']:"";?>">
											</a>
										</dt>
										<dd>
											<div class="fl">
												<p class="name">
													<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" title="<?php echo isset($item['title'])?$item['title']:"";?>" target="_blank"><?php echo isset($item['title'])?$item['title']:"";?></a>
												</p>
														<p class="explain" title="&#160;">
															<span class="gzmc_k"></span>
															<span class="gzmc_z"></span>
														</p>
														<p class="prom" title="">
																已有<span class="dazhe"><?php echo isset($item['sum_count'])?$item['sum_count']:"";?></span>人团
														</p>
											</div>
											<div class="fr">
												<p class="brand-logo">
												</p>
												<p class="surplus countdown" id="timeRemaining-<?php echo isset($k)?$k:"";?>">
													<span class="icon">剩余</span>
													<span id='cd_day_<?php echo isset($k)?$k:"";?>'></span>天
													<span id='cd_hour_<?php echo isset($k)?$k:"";?>'></span>小时
													<span id='cd_minute_<?php echo isset($k)?$k:"";?>'></span>分
													<span id='cd_second_<?php echo isset($k)?$k:"";?>'></span>秒	
													<input name='endTime' type='hidden' value='<?php echo isset($end)?$end:"";?>' />
												</p>
											</div>
											<div class="clear"></div>
										</dd>
									</dl>
									<div class="brand-icon" id="brandsale_sold-out-_0">
										<div class="clear"></div>
									</div>
								</li>
							<?php }?>
						</ul>
						<div class="clear"></div>
					</div>
				</div>
		</div>
</div>
<?php }?>
		<div class="clear"></div>
		<div class="next_more" style='margin-top:20px;'>
			<span class="next_more_btn" id="nextgroup" onclick="loadPromotion('<?php echo IUrl::creatUrl("/site/getMoreTuan");?>');">
				<span class="next_more_text">查看下一组</span>
			</span>
		</div> 
</div>
<script type='text/html' id='promotion_box'>
	<li style="margin-top:0;">
		<dl>
			<dt class="brand-img">
				<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/");?>/<%=id%>" target="_blank" title="<%=title%>">
					<img class="lazyload img"  src="<?php echo IUrl::creatUrl("<%=img%>");?>" alt="<%=title%>">
				</a>
			</dt>
			<dd>
				<div class="fl">
					<p class="name">
						<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/");?>/<%=id%>" title="<%=title%>" target="_blank"><%=title%></a>
					</p>
							<p class="explain" title="&#160;">
								<span class="gzmc_k"></span>
								<span class="gzmc_z"></span>
							</p>
							<p class="prom" title="">
									已有<span class="dazhe"><%=sum_count%></span>人团
							</p>
				</div>
				<div class="fr">
					<p class="brand-logo">
					</p>
					<p class="surplus countdown" id="timeRemaining-<%=key%>">
						<span class="icon">剩余</span>
						<span id='cd_day_<%=key%>'></span>天
						<span id='cd_hour_<%=key%>'></span>小时
						<span id='cd_minute_<%=key%>'></span>分
						<span id='cd_second_<%=key%>'></span>秒	
						<input name='endTime' type='hidden' value='<%=end%>' />
					</p>
				</div>
				<div class="clear"></div>
			</dd>
		</dl>
		<div class="brand-icon" id="brandsale_sold-out-_0">
			<div class="clear"></div>
		</div>
	</li>
</script>

</div>
<?php }else{?>
	<link href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/tm.css";?>" rel="stylesheet">
<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/shan/shan.js";?>"></script>
<div class="cnt w1190">
<style type="text/css">
	h1{font-family:"微软雅黑";font-size:20px;margin-top:20px;letter-spacing:2px;}

	.time-item strong{background:#C71C60;color:#fff;line-height:49px;font-size:18px;font-family:Arial;padding:0 5px;margin-right:5px;}
	.time-item a:hover{color:#c71c60}
	#day_show{float:left;line-height:49px;color:#c71c60;font-size:14px;margin:0 5px;font-family:Arial, Helvetica, sans-serif;}
	.item-title .unit{background:none;line-height:49px;font-size:12px;padding:0 5px;float:left;}
	
	.pp_tm_show li{
		position: relative;
		float: left;
		width: 435px;
		height: 300px;
		margin: 20px 10px 0 0;
		padding: 10px;
		border: 1px solid #ccc;
		background: #fff;overflow:hidden;
		font-size:16px;
		
	}
	.pp_tm_show li:hover {
		background: #f15a24;
		border: 1px solid #f15a24;
	}
	.pp_tm_show dd{margin-top:15px;}

	.pp_tm_show .pl{float:left;}
	.pp_tm_show .pr{float:right;}

</style>

<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/base.css";?>">
<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index_sg.css";?>">
<!--tuan-->
<link type="text/css" rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/resource.css";?>">
<!--tuan-->
<!-- 团购活动列表 -->


<div class="pp_tm_con_fr">
	<a name="floor-1"></a>
		<h3>今日团购<i>TOP</i></h3>
</div>
<div class="mb10 warp-all group-list">
	<input name='start' type='hidden' value='<?php echo isset($this->count)?$this->count:"";?>'/>
	  <div class="left_group">
	  	<?php foreach($this->todayList as $key => $item){?>
		<?php $end=strtotime($item['end_time'])?>
		<?php $zhe = 10*round($item['regiment_price']/$item['sell_price'],2)?>
	        <div class="today-deals-con">
		        <div class="deal-img-wrap">
		            <a title="" href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" target="_blank"><img src="<?php echo IUrl::creatUrl("".$item['img']."");?>" alt=""  width="350" height="350"></a>
		            <div class="all_bicon_box"></div>
		        </div>
		        <div class="deal-summary"> 
				<span class="bk-timeleft home_timer today_end_time countdown" id="timeRemaining-<?php echo isset($key)?$key:"";?>">
					<span class="icon">距团购结束</span>
					<span id='cd_day_<?php echo isset($key)?$key:"";?>'></span>天
					<span id='cd_hour_<?php echo isset($key)?$key:"";?>'></span>小时
					<span id='cd_minute_<?php echo isset($key)?$key:"";?>'></span>分
					<span id='cd_second_<?php echo isset($key)?$key:"";?>'></span>秒	
					<input name='endTime' type='hidden' value='<?php echo isset($end)?$end:"";?>' />
				</span>
		            <a class="deal-summary-link" target="_blank" href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>}">
		                <p class="tit"> <em><?php echo isset($zhe)?$zhe:"";?>折/</em><?php echo isset($item['intro'])?$item['intro']:"";?></p>
		                <p class="price-wrap">
		                    <span class="price"><em>¥</em><?php echo isset($item['regiment_price'])?$item['regiment_price']:"";?></span>
		                    <span class="dis"><del>¥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></del></span> 
		                </p>
		                <div class="other clearfix">
		                  <span class="buy_num_con"><span class="buy_number"><?php echo isset($item['sum_count'])?$item['sum_count']:"";?></span>人已经购买</span>
		                </div>
		                <p class="summary-btn"> <span class="deal-btn">去看看</span>
		                </p>
		            </a>
		        </div>
	   		 </div>
		<?php }?> 
	</div>
    <div class="right_group">
		<a href="" target="_blank">
			<img id="pic_adv_297" style="width:300px;height:333px" border="0" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/tuan1.png";?>" alt="团购右侧1">
		</a>   
		 <a href="" target="_blank">
		 	<img id="pic_adv_298" style="width:300px;height:333px" border="0" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/tuan2.jpg";?>" alt="">
		 </a>  
	</div>
</div>

<div class="clear"></div>

<style>
	.shan-brand li {width:358px;height:280px;}
	.shan-brand li dt img { max-width:358px;max-height:220px; }
	.shan-brand .brand-img {width:358px;height:220px;}
	.pp_tm_con_se{width:1190px;}
	.pp_tm_con_se_l{width:1190px;}
	.width { width:1200px;margin:0 auto;}
</style>
<?php if(isset($this->brandList)&&$this->brandList){?>
<div class="pp_tm_con">
		  <div class="pp_tm_con_fr"><a name="floor-2"></a>
			<h3>品牌团<i>Brand</i></h3>
		  </div>
			<div class="pp_tm_con_se">
				<div class="pp_tm_con_se_l">
				  <!--品牌特卖 -->
					<div class="shan-brand width">
						<ul class="shan-all-li">
							<?php foreach($this->brandList as $key => $item){?>
							<?php $end=strtotime($item['end_time']);$k=$key+2;?>
								<li style="margin-top:0;">
									<dl>
										<dt class="brand-img">
											<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" target="_blank" title="<?php echo isset($item['title'])?$item['title']:"";?>">
												<img class="lazyload img"  src="<?php echo IUrl::creatUrl("".$item['img']."");?>" alt="<?php echo isset($item['title'])?$item['title']:"";?>">
											</a>
										</dt>
										<dd>
											<div class="fl">
												<p class="name">
													<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/".$item['id']."");?>" title="<?php echo isset($item['title'])?$item['title']:"";?>" target="_blank"><?php echo isset($item['title'])?$item['title']:"";?></a>
												</p>
														<p class="explain" title="&#160;">
															<span class="gzmc_k"></span>
															<span class="gzmc_z"></span>
														</p>
														<p class="prom" title="">
																已有<span class="dazhe"><?php echo isset($item['sum_count'])?$item['sum_count']:"";?></span>人团
														</p>
											</div>
											<div class="fr">
												<p class="brand-logo">
												</p>
												<p class="surplus countdown" id="timeRemaining-<?php echo isset($k)?$k:"";?>">
													<span class="icon">剩余</span>
													<span id='cd_day_<?php echo isset($k)?$k:"";?>'></span>天
													<span id='cd_hour_<?php echo isset($k)?$k:"";?>'></span>小时
													<span id='cd_minute_<?php echo isset($k)?$k:"";?>'></span>分
													<span id='cd_second_<?php echo isset($k)?$k:"";?>'></span>秒	
													<input name='endTime' type='hidden' value='<?php echo isset($end)?$end:"";?>' />
												</p>
											</div>
											<div class="clear"></div>
										</dd>
									</dl>
									<div class="brand-icon" id="brandsale_sold-out-_0">
										<div class="clear"></div>
									</div>
								</li>
							<?php }?>
						</ul>
						<div class="clear"></div>
					</div>
				</div>
		</div>
</div>
<?php }?>
		<div class="clear"></div>
		<div class="next_more" style='margin-top:20px;'>
			<span class="next_more_btn" id="nextgroup" onclick="loadPromotion('<?php echo IUrl::creatUrl("/site/getMoreTuan");?>');">
				<span class="next_more_text">查看下一组</span>
			</span>
		</div> 
</div>
<script type='text/html' id='promotion_box'>
	<li style="margin-top:0;">
		<dl>
			<dt class="brand-img">
				<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/");?>/<%=id%>" target="_blank" title="<%=title%>">
					<img class="lazyload img"  src="<?php echo IUrl::creatUrl("<%=img%>");?>" alt="<%=title%>">
				</a>
			</dt>
			<dd>
				<div class="fl">
					<p class="name">
						<a href="<?php echo IUrl::creatUrl("/site/tuan_product/active/");?>/<%=id%>" title="<%=title%>" target="_blank"><%=title%></a>
					</p>
							<p class="explain" title="&#160;">
								<span class="gzmc_k"></span>
								<span class="gzmc_z"></span>
							</p>
							<p class="prom" title="">
									已有<span class="dazhe"><%=sum_count%></span>人团
							</p>
				</div>
				<div class="fr">
					<p class="brand-logo">
					</p>
					<p class="surplus countdown" id="timeRemaining-<%=key%>">
						<span class="icon">剩余</span>
						<span id='cd_day_<%=key%>'></span>天
						<span id='cd_hour_<%=key%>'></span>小时
						<span id='cd_minute_<%=key%>'></span>分
						<span id='cd_second_<%=key%>'></span>秒	
						<input name='endTime' type='hidden' value='<%=end%>' />
					</p>
				</div>
				<div class="clear"></div>
			</dd>
		</dl>
		<div class="brand-icon" id="brandsale_sold-out-_0">
			<div class="clear"></div>
		</div>
	</li>
</script>

<?php }?>

<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/qqkf.css";?>">
<div class="service">
		<ul class="w1190 clearfix">
			<li class="item1">
				<p class="t18">正品优选</p>
				<span>共享集团供应链</span>
			</li>
			<li class="item2">
				<p class="t18">私人定制 </p>
				<span>特色服务 品质保证</span>
			</li>
			<li class="item3">
				<p class="t18">售后无忧 </p>
				<span>7天无理由退货</span>
			</li>
			<li class="item4">
				<p class="t18">郊区次日达 </p>
				<span>专业物流 及时送达</span>
			</li>
			<li class="item5">
				<p class="t18">满79包邮 </p>
				<span>轻松购物 超值贴心</span>
			</li>
		</ul>
	</div>
<!-- help-->
<div class="help">
	<div class="w1190 clearfix">
		<?php $cls=array('help-new','help-delivery','help-pay','help-user','help-service');?>
		<?php foreach(Api::run('getHelpCategoryFoot') as $key => $helpCat){?>
		<dl class="<?php echo isset($cls[$key])?$cls[$key]:"";?>">
			<dt class="t14"><?php echo isset($helpCat['name'])?$helpCat['name']:"";?></dt>
			<?php foreach(Api::run('getHelpListByCatidAll',array('#cat_id#',$helpCat['id'])) as $key => $item){?>
			<?php if($item['link']!=''){?>
			<dd><a href="<?php echo IUrl::creatUrl("".$item['link']."");?>" style='color:#333;'><?php echo isset($item['name'])?$item['name']:"";?></a></dd>
			<?php }else{?>
			<dd><a href="<?php echo IUrl::creatUrl("/site/help/id/".$item['id']."");?>" style='color:#333;'><?php echo isset($item['name'])?$item['name']:"";?></a></dd>
			<?php }?>
						
			<?php }?>
		</dl>
		<?php }?>
		
		<div class="contact f-l">
			<div class="contact-border clearfix">
				<p>
					<span>山城速购APP</span>&nbsp;&nbsp;&nbsp;&nbsp;<span>山城速购官方微信</span>
				</p>
				<p class="erweima">
					&nbsp;<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/erweima.png";?>" style="width:80px;height:80px;">&nbsp;<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/erweima.png";?>" style="width:80px;height:80px;" class="weixin">
				</p>
			</div>
		</div>
    </div>
</div>


<!-- help-->

<script type="text/javascript">
$(function()
{
		$('input:text[name="word"]').val("输入关键字...");

	$('input:text[name="word"]').bind({
		keyup:function(){autoComplete('/site/autoComplete','/search.html?word=@word@','');}
	});

	var mycartLateCall = new lateCall(200,function(){showCart('/simple/showCart')});

	//购物车div层
	$('.mycart').hover(
		function(){
			mycartLateCall.start();
		},
		function(){
			mycartLateCall.stop();
			$('#div_mycart').hide('slow');
		}
	);
	
	$("#jCat .list-item").hover(function(){
	    $(this).addClass("cat-hover");
	  	$(this).children(".cat-pop").show().append($('#nav-ad').clone().css('display','block'));
	  },function(){
		 $(this).removeClass("cat-hover");
		 $(this).children(".cat-pop").hide().find('#nav-ad').remove();
	});

	  $("#jSiteNavQuick li:first").hover(function(){
	    $(this).children("a").addClass("menu-hd-hover");
		$(this).children(".menu-bd").addClass("menu-bd-hover");
	  },function(){
		 $(this).children("a").removeClass("menu-hd-hover");
		$(this).children(".menu-bd").removeClass("menu-bd-hover");
	  });


	   $(".tab-title li").hover(function(){
	   	$(this).addClass('tab-title-hover').siblings().removeClass('tab-title-hover');
		var p= $(this).index();
		$(this).parent().parent().children(".tab-cnt").children(".tab-item").eq(p).show().siblings().hide();

	  });


});

//[ajax]加入购物车
function joinCart_ajax(id,url,type)
{
	$.getJSON(url,{"goods_id":id,"type":type,"random":Math.random()},function(content){
		if(content.isError == false)
		{
			var count = parseInt($('[name="mycart_count"]').html()) + 1;
			$('[name="mycart_count"]').html(count);
			$('.msgbox').hide();
			alert(content.message);
		}
		else
		{
			alert(content.message);
		}
	});
}

//列表页加入购物车统一接口
function joinCart_list(id)
{
	$.getJSON('/simple/getProducts',{"id":id},function(content){
		if(!content)
		{
			joinCart_ajax(id,'goods');
		}
		else
		{
			var url = "/block/goods_list/goods_id/@goods_id@/type/radio/is_products/1";
			url = url.replace('@goods_id@',id);
			artDialog.open(url,{
				id:'selectProduct',
				title:'选择货品到购物车',
				okVal:'加入购物车',
				ok:function(iframeWin, topWin)
				{
					var goodsList = $(iframeWin.document).find('input[name="id[]"]:checked');

					//添加选中的商品
					if(goodsList.length == 0)
					{
						alert('请选择要加入购物车的商品');
						return false;
					}
					var temp = $.parseJSON(goodsList.attr('data'));

					//执行处理回调
					joinCart_ajax(temp.product_id,'product');
					return true;
				}
			})
		}
	});
}
</script>

<div class="copyright">
	<?php echo IFilter::stripSlash($siteConfig->site_footer_code);?>
	
</div> 



<div class="bottom">
<div class="pic_link">
<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgMBmVDzwyaAaIMBAAAJZgSEr6I65200.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgMBmVLfYZGALWNHAAAOxFbda9472600.gif";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCr1Dzwj2AVUL0AAA-ic2BxNw39500.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCrlPYTqCASlHXAAAd82JE0eA31000.png";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCtlDzwg-ADxhpAABCSLsmSeE68100.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQI0FV6Ug2AWBFSAAASB0seI2g11300.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgMBmVDzwyaAaIMBAAAJZgSEr6I65200.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCr1Dzwj2AVUL0AAA-ic2BxNw39500.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCtlDzwg-ADxhpAABCSLsmSeE68100.jpg";?>" alt="">
</a>

<a href="https://online.unionpay.com/" target="_blank" rel="nofollow">
<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/CgQCrlPYTqCASlHXAAAd82JE0eA31000.png";?>" alt="">
</a>
</div><br/></div>

<script type="text/javascript">

	

$(function () {
    $(window).scroll(function () {
        var scrollTop = $(document).scrollTop();
        var documentHeight = $(document).height();
        var windowHeight = $(window).height();
        var contentItems = $("#w1190").find(".item");
        var currentItem = "";

        if (scrollTop+windowHeight==documentHeight) {
            currentItem= "#" + contentItems.last().attr("id");
        }else{
            contentItems.each(function () {
                var contentItem = $(this);
                var offsetTop = contentItem.offset().top;
                if (scrollTop > offsetTop - 100) {//此处的100视具体情况自行设定，因为如果不减去一个数值，在刚好滚动到一个div的边缘时，菜单的选中状态会出错，比如，页面刚好滚动到第一个div的底部的时候，页面已经显示出第二个div，而菜单中还是第一个选项处于选中状态
                    currentItem = "#" + contentItem.attr("id");
                }
            });
        }
        if (currentItem != $("#floornav").find(".cur").attr("data")) {
            $("#floornav").find(".cur").removeClass("cur");
            $("#floornav").find("[data=" + currentItem + "]").addClass("cur");
        };

    });
});


window.onscroll = function () {
    if (document.documentElement.scrollTop + document.body.scrollTop > 500) {
        $("#floornav").fadeIn(300);
    }
    else {
        $("#floornav").fadeOut(300);
    }
}


$(function(){ $("#floornav a").click(function(){$("html,body").animate({scrollTop:$($(this).attr("data")).offset().top}, 500); }) });
</script>

<?php if($this->isIndex){?>
<link type="text/css" rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/global_site_index_new.css";?>">
<!--楼层电梯 -->
<div class="floor_left_box" id="floornav" data-tpa="YHD_HOMEPAGE_FLOORNAV" style="display:none;">

<a href="#floor-1" data="#floor-1" rel="floor-1" class="cur">
<i class="left_iconfont ">猜你喜欢</i>
<em class="two_line">猜你喜欢</em>
</a>
<?php foreach($categoryTop as $key => $item){?>
<?php $K=$key+2?>
<a href="#floor-<?php echo isset($K)?$K:"";?>" data="#floor-<?php echo isset($K)?$K:"";?>" rel="floor-<?php echo isset($K)?$K:"";?>">
<i class="left_iconfont "><?php echo isset($item['name'])?$item['name']:"";?></i>
<em class="two_line"><?php echo isset($item['name'])?$item['name']:"";?></em>
</a>
<?php }?>
<?php }?>
<!-- 
<a href="javascript:;" data="#toTop" rel="toTop">
<i class="left_iconfont ">返回顶部</i>
<em class="two_line">返回顶部</em>
</a> -->

</div> 



<!--悬浮导航-->


<!--jdgaofang-->

<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/nav2.js";?>"></script>
<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/nav.css";?>" type="text/css">

		<div id="J-global-toolbar">
		<div class="toolbar-wrap J-wrap ">
			<div class="toolbar">
				<div class="toolbar-panels J-panel">
					<div style="visibility: hidden;" class="J-content toolbar-panel tbar-panel-follow">
						<h3 class="tbar-panel-header J-panel-header">
							<a href="#" target="_blank" class="title"> 
								<i><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic4-4.png";?>"></i> 
								<em class="title">我的关注</em>
							</a> 
							<span class="close-panel J-close"></span>
						</h3>
						<div class="tbar-panel-main">
							<div style="height: 943px;" class="tbar-panel-content J-panel-content">
								<div class="jt-history-wrap">
									
									<ul>
										
										<?php if(isset($favorite_ids)&&$favorite_ids){?>
										<?php foreach(Api::run('getGoodsInfoByIds',array('#ids#',$favorite_ids)) as $key => $item){?>
										
										<li class="jth-item"><a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['goods_id']."");?>" class="img-wrap"
											target="_blank"
											title="<?php echo isset($item['name'])?$item['name']:"";?>"> <img
												src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>"
												height="100" width="100">
										</a> <a class="add-cart-button" href="javascript:void(0)" onclick='joinCart_ajax(<?php echo isset($item["goods_id"])?$item["goods_id"]:"";?>,"<?php echo IUrl::creatUrl("/simple/joinCart");?>","goods")'>加入购物车</a>
											<a href="#" target="_blank" class="price">￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></a>
										</li>
										<?php }?>
										<?php }?>
									</ul>
									<a href="<?php echo IUrl::creatUrl("/ucenter/favorite");?>" class="history-bottom-more" target="_blank">查看更多关注商品
										&gt;&gt;</a>
								</div>
							</div>
						</div>
						<div class="tbar-panel-footer J-panel-footer"></div>
					</div>
					<div style="visibility: hidden;" class="J-content toolbar-panel tbar-panel-history toolbar-animate-in">
						<h3 class="tbar-panel-header J-panel-header">
							<a href="#" target="_blank" class="title"> <i><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic3-3.png";?>"></i> 
								<em class="title">我的足迹</em>
							</a> 
							<span class="close-panel J-close"></span>
						</h3>
						<div class="tbar-panel-main">
							<div style="height: 943px;" class="tbar-panel-content J-panel-content">
								<div class="jt-history-wrap">
									
									<ul>
										<?php if($this->user){?><?php $user_id = $this->user['user_id']?>
										<?php }else{?><?php $user_id = false;?>
										<?php }?>
										<?php $history=user_like::get_user_history($user_id)?>
										<?php $ids='';?>
										<?php if($history!=null){?>
										<?php foreach($history as $key => $item){?>
											<?php $ids .=$item['goods_id'].',';?>
										<?php }?>
										<?php $ids=substr($ids,0,-1);?>
										
										
										<?php foreach(Api::run('getGoodsInfoByIds',array('#ids#',$ids)) as $key => $item){?>
										
										<li class="jth-item"><a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['goods_id']."");?>" class="img-wrap"
											target="_blank"
											title="<?php echo isset($item['name'])?$item['name']:"";?>"> <img
												src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/170/h/170");?>"
												height="100" width="100">
										</a> <a class="add-cart-button" href="javascript:void(0)" onclick='joinCart_ajax(<?php echo isset($item["goods_id"])?$item["goods_id"]:"";?>,"<?php echo IUrl::creatUrl("/simple/joinCart");?>","goods")'>加入购物车</a>
											<a href="#" target="_blank" class="price">￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></a>
										</li>
										<?php }?>
										<?php }?>
									</ul>
									<a href="<?php echo IUrl::creatUrl("/ucenter/history");?>" class="history-bottom-more" target="_blank">查看更多足迹商品
										&gt;&gt;</a>
								</div>
							</div>
						</div>
						<div class="tbar-panel-footer J-panel-footer"></div>
					</div>
					<!--在线客服pannel-->
					<div style="visibility: hidden;" class="J-content toolbar-panel tbar-panel-chat">
						<h3 class="tbar-panel-header J-panel-header">
							<a href="#" target="_blank" class="title"> 
								<i></i> 
								<em class="title">在线客服</em>
							</a> 
							<span class="close-panel J-close"></span>
						</h3>
						<div class="tbar-panel-main">
							<div style="height: 943px;" class="tbar-panel-content J-panel-content">
								<div class="jt-online-wrap">
									<div class="n_bk">
									<?php $service=Sonline::getService()?>
									<?php if($service){?>
										<table  width="100%" >
											<col width="90px" />
											<col />
											<?php foreach($service['qq'] as $key => $item){?>
											<tr height='35'>
												<th align='right'><?php echo isset($item['name'])?$item['name']:"";?>：</th>
												<td style='padding-left:10px;'>
													<a target="_blank" href="<?php echo isset($item['link'])?$item['link']:"";?>" >
														<img border="0"  src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/qq.jpg";?>"/>
													</a>
												</td>
											</tr>
											<?php }?>
											
										</table>
										<?php }?>
									</div>
								</div>
							</div>
						</div>
						<div class="tbar-panel-footer J-panel-footer"></div>
					</div>
				</div>
				<div class="toolbar-header"></div>
				<div class="toolbar-tabs J-tab">
					<div class="toolbar-tab tbar-tab-user   ">
						<i class="tab-ico"></i>
						 <a target='blank' href='<?php echo IUrl::creatUrl("/ucenter/");?>'>
						 	<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic1.png";?>">
						 	<em class="tab-text "> 个人中心</em>
						 </a>
						<span class="tab-sub J-count hide">1</span>
					</div>
					<div class=" toolbar-tab tbar-tab-follow">
						<i class="tab-ico"></i> <img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic4.png";?>">
						<em class="tab-text">我的关注</em> 
						<span class="tab-sub J-count hide">0</span>
					</div>
					<div class=" toolbar-tab tbar-tab-history ">
						<i class="tab-ico"></i> <img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic3.png";?>"> <em class="tab-text">我的足迹</em> <span
							class="tab-sub J-count hide">0</span>
					</div>
					<div class=" toolbar-tab tbar-tab-chat ">
						<i class="tab-ico"></i><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/ic2.png";?>"> <em class="tab-text">客服QQ</em> <span
							class="tab-sub J-count hide">0</span>
					</div>

				</div>
				<div class="toolbar-footer">
					<div class="toolbar-tab tbar-tab-top" id="tbar-tab-top">
						<a href="#"> 
							<i class="tab-ico"></i> 
						</a>
					</div>
					<a href="<?php echo IUrl::creatUrl("/ucenter/complain");?>" target="_blank"> 
						<div class=" toolbar-tab tbar-tab-feedback">
							<i class="tab-ico"></i> 
							<em class="footer-tab-text ">反馈</em>
						</div>
					</a>
				</div>
				<div class="toolbar-mini"></div>
			</div>
			<div id="J-toolbar-load-hook"></div>
		</div>
	</div>







 
<div style="display: none; position: fixed; left: 0px; top: 0px; width: 100%; height: 100%; cursor: move; opacity: 0; background-color: rgb(255, 255, 255); background-position: initial initial; background-repeat: initial initial;"></div> 

<script type="text/javascript">
var _mvq = window._mvq || []; 
window._mvq = _mvq;
_mvq.push(['$setAccount', 'm-183213-0']);

_mvq.push(['$setGeneral', '', '', /*用户名*/ '', /*用户id*/ '']);//如果不传用户名、用户id，此句可以删掉
_mvq.push(['$logConversion']);
(function() {
	var mvl = document.createElement('script');
	mvl.type = 'text/javascript'; mvl.async = true;
	mvl.src = ('https:' == document.location.protocol ? 'https://static-ssl.mediav.com/mvl.js' : 'http://static.mediav.com/mvl.js');
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(mvl, s); 
})();	

</script>
</body>

</html>