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
		function sign_point(){
		$.ajax({
			type : 'post',
			async:true,
			data:{},
			dataType:'json',
			url:'<?php echo IUrl::creatUrl("/ucenter_ajax/sign_add_point");?>',
			beforeSend:function(){
				
			},
			success:function(data){
				if(data==1){
					alert('签到成功');
				}
			},
			error:function(){
				
			},
			complete:function(){
			},
			timeout:1000,
		})
	}
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
						<a  href="javascript:void(0)">
							<b><?php echo isset($this->user['point'])?$this->user['point']:"";?></b>
							<span>积分<em><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/qiandao.gif";?>" onclick='sign_point()'/></em></span>
							
						</a>
						<a  href="<?php echo IUrl::creatUrl("/ucenter/account_log");?>">
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
	<?php 
	$seo_data=array();
	$site_config=new Config('site_config');
	$seo_data['title']=$name."_".$site_config->name;
	$seo_data['keywords']=$keywords;
	$seo_data['description']=$description;
	seo::set($seo_data);
	
?>

<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/cookie/jquery.cookie.js"></script>
<?php $breadGuide = goods_class::catRecursion($category);$promotion=Api::run('getProrule');?>
<?php foreach($promotion as $key => $item){?>
	<?php if($item['type']==6){?><!--获取免配送费的信息-->
	<?php $freightInfo=$item['info']?>
	<?php }?>
<?php }?>
<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index1.css";?>">
<link rel="stylesheet" type='text/css' href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/detail.css";?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/jquery.bxSlider/jquery.bxslider.css";?>" />
<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/jquery.bxSlider/jquery.bxSlider.min.js";?>"></script>
<script type='text/javascript' src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/piczoom.js";?>"></script>
<div class="position"><span>您当前的位置：</span><a href="<?php echo IUrl::creatUrl("");?>">首页</a><?php foreach($breadGuide as $key => $item){?> » <a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a><?php }?> » <?php echo isset($name)?$name:"";?></div>
<div class="wrapper clearfix">
	
	<div class="summary" style='margin-right:15px;'>

		<!--货品ID，当为商品时值为空-->
		<input type='hidden' id='product_id' alt='货品ID' value='' />
		<input type='hidden' name='promo_name' value="<?php echo isset($promo_data['promo_type'])?$promo_data['promo_type']:"";?>"/><!--活动类型（闪购。。)-->
		<input type='hidden' name='active_id' value="<?php echo isset($promo_data['active_id'])?$promo_data['active_id']:"";?>"/><!--活动id-->
		<!--基本信息区域-->
		<div id='detail'>
			<div id="J_DetailMeta" class="tm-detail-meta tm-clear">
			<div class="tb-wrap">
				<div class="tb-detail-hd">
					<h1 data-spm="1000983">	<?php echo isset($name)?$name:"";?> </h1>
					<h2 data-spm="1000983" style="color:#e05757;">	<?php echo isset($short_desc)?$short_desc:"";?> </h2>
					<p> </p>
				</div>
				<div class="tm-fcs-panel" id='price_panel'>
					
				</div>
				<script  type='text/html' id='priceTemplate'>
					<%if(shan_price && group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price" style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%if(shan_price<=group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">会员价</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price"  style='text-decoration:line-through;'><%=group_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">闪购价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price'><%=shan_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else{%>
							<dl id="J_PromoPrice" class="tm-promo-panel"  >
								<dt class="tb-metatit">闪购价</dt>
								<dd><em class="tm-yen">¥</em>
									<span class="tm-price" style='text-decoration:line-through;'><%=shan_price%></span>
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
							<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
								<dt class="tb-metatit">会员价</dt>
								<dd>
									<em class="tm-yen">¥</em>
									<span class="tm-price" id='real_price'><%=group_price%></span>
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
						<%}%>
						
					<%}else{%>
						<!---->
						<%if(shan_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price' style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">闪购价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price'><%=shan_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else if(group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price"  style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">会员价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" ><%=group_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else{%>
							<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
								<dt class="tb-metatit">价格</dt>
								<dd>
									<em class="tm-yen">¥</em>
									<%if(minSellPrice != maxSellPrice){%>
										<span class="tm-price" id='real_price'><%=minSellPrice%>---</span>
										<em class="tm-yen">¥</em>
										<span class="tm-price" id='real_price'><%=maxSellPrice%></span>
									<%}else{%>
										<span class="tm-price" id='real_price'><%=sell_price%></span>
									<%}%>
									
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
						<%}%>
						
					<%}%>
				</script>
				<div class="tb-meta">
					<dl id="J_RSPostageCont" class="tm-delivery-panel">
						<dt class="tb-metatit">运费</dt>
						<dd>
							<div class="tb-postAge" style='color:#666;'>
								<?php echo isset($freightInfo)?$freightInfo:"";?>
								<a href="<?php echo IUrl::creatUrl("/site/help/id/73");?>" style='color:blue;'>配送说明</a>
							</div>
						</dd>
					</dl>
				</div>
				<div class="tb-meta">
					<dl id="J_RSPostageCont" class="tm-delivery-panel">
						<dt class="tb-metatit">服务</dt>
						<dd>
							<div class="tb-postAge">
							由<?php if(isset($seller)){?>
							<?php echo isset($seller['true_name'])?$seller['true_name']:"";?>
							<?php }else{?>
							山城速购
							<?php }?>
							配送,并提供售后服务
							</div>
						</dd>
					</dl>
				</div>
					<ul class="tm-ind-panel">
						<li class="tm-ind-item tm-ind-sellCount canClick" >
							<div class="tm-indcon" onclick="jumpTo('buyHistory')">
									<span class="tm-label">销售记录</span>
								<span class="tm-count"><?php echo isset($buy_num)?$buy_num:"";?></span>
							</div>
						</li>
						<li id="J_ItemRates" class="tm-ind-item tm-ind-reviewCount canClick tm-line3">
							<div class="tm-indcon"  onclick="jumpTo('point')">
								<span class="tm-label">累计评价</span>
								<span class="tm-count"><?php echo isset($comments)?$comments:"";?></span>
							</div>
						</li>
						<li class="tm-ind-item tm-ind-emPointCount" >
							<div class="tm-indcon">
								<a target="_blank" href="" >
									<span class="tm-label">送积分</span>
									<span class="tm-count"><?php echo isset($point)?$point:"";?></span>
								</a>
							</div>
						</li>
					</ul>
					<div class="tb-key">
						<div class="tb-skin">
							<div class="tb-sku">
								
								<?php if($spec_array){?>
								<?php $specArray = JSON::decode($spec_array);?>
								<?php foreach($specArray as $key => $item){?>
								<dl class="m_10 clearfix" name="specCols">
									<dt><?php echo isset($item['name'])?$item['name']:"";?></dt>
									<dd class="w_45" style="margin-left:67px;" id="specList<?php echo isset($item['id'])?$item['id']:"";?>">
										<?php $specVal=explode(',',trim($item['value'],','))?>
										<?php foreach($specVal as $key => $spec_value){?>
										<?php if($item['type'] == 1){?>
										<div class="item w_27"><a href="javascript:void(0);" onclick="sele_spec(this);" value='{"id":"<?php echo isset($item['id'])?$item['id']:"";?>","type":"<?php echo isset($item['type'])?$item['type']:"";?>","value":"<?php echo isset($spec_value)?$spec_value:"";?>","name":"<?php echo isset($item['name'])?$item['name']:"";?>"}' ><?php echo isset($spec_value)?$spec_value:"";?><span></span></a></div>
										<?php }else{?>
										<div class="item"><a href="javascript:void(0);" onclick="sele_spec(this);" value='{"id":"<?php echo isset($item['id'])?$item['id']:"";?>","type":"<?php echo isset($item['type'])?$item['type']:"";?>","value":"<?php echo isset($spec_value)?$spec_value:"";?>","name":"<?php echo isset($item['name'])?$item['name']:"";?>"}' ><img src="<?php echo IUrl::creatUrl("")."".$spec_value."";?>" width='30px' height='30px' /><span></span></a></div>
										<?php }?>
										<?php }?>
									</dd>
								</dl>
								<?php }?>
								<?php }?>
																								
								<dl class="tb-amount tm-clear">
									<dt class="tb-metatit">数量</dt>
									<dd id="J_Amount">
										<input class="gray_t f_l" type="text" id="buyNums" onblur="checkBuyNums();" value="1" maxlength="5" />
											<div class="resize">
												<a class="add" href="javascript:modified(1);"></a>
												<a class="reduce" href="javascript:modified(-1);"></a>
											</div>
											<span class="mui-amount-unit">&nbsp;件</span>
										<em id="J_EmStock" class="tb-hidden" style="display: inline;">库存<label id="data_storeNums"><?php echo isset($store_nums)?$store_nums:"";?></label>件</em>
										<span id="J_StockTips"> </span>
									</dd>
								</dl>
							</div>
						</div>
					</div>
					
					
				</div>
			</div>
		</div>

		<!--购买区域-->
		<div class="current">
			<?php if($store_nums <= 0){?>
				该商品已售完，不能购买，您可以看看其它商品！(<a href="<?php echo IUrl::creatUrl("/simple/arrival/goods_id/".$id."");?>" class="orange">到货通知</a>)
			<?php }else{?>

			<input class="submit_buy" type="button" id="buyNowButton" onclick="buy_now();" value="立即购买" />

						<div class="shop_cart" style="z-index:1">
				<input class="submit_join" type="button" id="joinCarButton" onclick="joinCart();" value="加入购物车" />

				<div class="shopping" id="product_myCart" style='display:none'>
					<dl class="cart_stats">
						<dt class="gray f14 bold">
							<a class="close_2 f_r" href="javascript:closeCartDiv();" title="关闭">关闭</a>
							<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/right_s.gif";?>" width="24" height="24" alt="" />成功加入购物车
						</dt>
						<dd class="gray">目前选购商品共<b class="orange" name='mycart_count'></b>件<span>合计：<b name='mycart_sum'></b></span></dd>
						<dd><a class="btn_blue bold" href="<?php echo IUrl::creatUrl("/simple/cart");?>">进入购物车</a><a class="btn_blue bold" href="javascript:void(0)" onclick="closeCartDiv();">继续购物>></a></dd>
					</dl>
				</div>
			</div>
			<?php }?>
		</div>
		
		<div class="tm-ser tm-clear">
			<dl class="tm-clear">
				<dt class="tb-metatit">服务承诺</dt>
				<dd class="tm-laysku-dd">
				<ul class="tb-serPromise">
					<li>
						<a target="_blank" title="按时发货" href=""> 按时发货 </a>
					</li>
					<li>
						<a target="_blank" title="七天无理由退还" href=""> 七天无理由退还 </a>
					</li>
					<li>
						<a target="_blank" title="品质保障" href=""> 品质保障 </a>
					</li>
				</ul>
			</dl>
		</div>

	</div>
		<!--图片放大器-->
		<div class="tb-gallery">
		<div class="tb-booth" id='bigPic'>
			<?php if($tag_data){?>
				<?php foreach($tag_data as $key => $item){?>
				<?php $right=$key*60?>
			 		<img src="<?php echo IUrl::creatUrl("")."".$item['img']."";?>" style='right:<?php echo isset($right)?$right:"";?>px' class="mark">
				<?php }?>
			<?php }?>
				
			<a target="_blank" rel="nofollow" href="" >
			<span class="ks-imagezoom-wrap">		
			
				<img id="J_ImgBooth"  name="bigpro"  src="" style='cursor:crosshair;'>
				<span class="ks-imagezoom-lens" style="position: absolute; border:1px solid blue;left:1px;"></span>
			</span>
			</a>
		</div>
		<div id='detShow' class="ks-overlay ks-imagezoom-viewer" style="width: 435px; height: 435px; left: 480px; top: 20px;display:none;">
			<div id="detPic" class="ks-overlay-content">
				<img style="position: absolute; top: 0px; left: 0px;" src="">
			</div>
		</div>
		<ul id="J_UlThumb" class="tb-thumb tm-clear">
			<?php if(count($photo)!=0){?>
			<?php foreach($photo as $key => $item){?>
			<li class="">
				<a href="#" onmouseover='showBig("<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/435/h/435");?>","<?php echo IUrl::creatUrl("")."".$item['img']."";?>")'>
					<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/60/h/60");?>">
				</a>
			</li>
			<?php }?>
			<?php }else{?>
				<li class="">
					<a href="#" onclick='javascript:void(0)'>
						<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>">
					</a>
				</li>
			<?php }?>
		</ul>
		<div style="margin-top:10px;margin-left:50px;">			
			<!-- JiaThis Button BEGIN -->
				<style>
					.bdshare_popup_box{z-index:100000000;}
					.bdshare_dialog_box{z-index:100000000;}
					.bd_weixin_popup_bg{z-index:100000000;}
					.bd_weixin_popup{z-index:100000000;}
				</style>

				
			<div class="bdsharebuttonbox">
				<a href="#" class="bds_more" data-cmd="more">分享到：</a>
				<a href="#" class="bds_tsina" data-cmd="tsina" title="分享到新浪微博">新浪微博</a>
			</div>
			<script>window._bd_share_config={"common":{"bdSnsKey":{},"bdText":"","bdMini":"2","bdMiniList":false,"bdPic":"","bdStyle":"0","bdSize":"16"},"share":{"bdSize":16}};with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];</script>

<!-- JiaThis Button END -->
			<?php $cls=isset($favorite_id_arr)&&in_array($id,$favorite_id_arr) ? 'guanzhu' : 'quguan'?>
				<div class='<?php echo isset($cls)?$cls:"";?>' style="line-height:26px;position:absolute; left:300px;bottom:1px; padding-left:17px; " id="gz"><a href='javascript:void(0)' onclick='favorite_add(this)'>我的关注：(<?php echo isset($favorite)?$favorite:"";?>人气)</a></div>
		</div>
	</div>

</div>
	
<script type='text/javascript'>
	$("#gz").click(function(){$(this).removeClass('quguan').addClass('guanzhu');});
</script>


<div class="wrapper clearfix container_2">

	<!--左边栏-->
	<div class="sidebar f_l">
		<?php if(isset($seller)){?>
		<!--商家-->
		<div class="box m_10">
			<div class="title"><a target='blank' href='<?php echo IUrl::creatUrl("/site/home/id/".$seller['id']."");?>'><?php echo isset($seller['true_name'])?$seller['true_name']:"";?></a></div>
			<div class="cont">
				<ul class="list">
					<?php $ave = statistics::getSellerGrade($seller['id']);$ave = $ave*70/5;?>
					<li style='background:none;'>评分：<span class="grade"><i style="width:<?php echo isset($ave)?$ave:"";?>px"></i></span></li>
					<li style='background:none;'>客服QQ:
						<a target="_blank" href="<?php echo Sonline::getChatUrl($seller['server_num']);?>" >
							<img border="0"  src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/qq.jpg";?>"/>
						</a>
					</li>
					<li style='background:none;'>
					<a class="btn_blue bold" href="<?php echo IUrl::creatUrl("/site/home/id/".$seller['id']."");?>">进入店铺</a>
					</li>
				</ul>
			</div>
		</div>
		<?php }?>
				<!--促销规则-->
		<div class="box m_10">
			<div class="title">促销活动</div>
			<div class="cont">
				<ul class="list">
				<?php foreach($promotion as $key => $item){?>
					<li><?php echo isset($item['info'])?$item['info']:"";?></li>
				<?php }?>
				</ul>
			</div>
		</div>
		<!--促销规则-->

		<!--购买推荐-->
		<?php if(isset($buyer_id) && $buyer_id){?>
				<div class="box m_10">
			<div class="title">购买本商品的用户还购买过</div>
			<div class="content" style='width:100%;'>
				<ul class="ranklist">
					<?php foreach(Api::run('getOrderGoodsByBuyerid',array('#buyer_id#',$buyer_id)) as $key => $item){?>
					<li class="current">
						<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><img width="58px" height="58px" src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/58/h/58");?>"></a>
						<a title="<?php echo isset($item['name'])?$item['name']:"";?>" class="p_name" href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a>
						<b>￥<?php echo isset($item['price'])?$item['price']:"";?></b>
					</li>
					<?php }?>
				</ul>
			</div>
		</div>
		<?php }?>
				<!--购买推荐-->

		<!--热卖商品-->
		<div class="box m_10">
			<div class="title">热卖商品</div>
			<div class="content" style='width:100%;'>
				<ul class="ranklist">
					<?php foreach(Api::run('getCommendHot') as $key => $item){?>
									<li class="current">
						<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><img width="58px" height="58px" alt="苹果" src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/58/h/58");?>" /></a>
						<a title="<?php echo isset($item['name'])?$item['name']:"";?>" class="p_name" href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a>
						<b>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></b>
					</li>
					<?php }?>
				</ul>
			</div>
		</div>
		<!--热卖商品-->
	</div>

	<!--滑动面tab标签-->
	<div class="main f_r" style="overflow:hidden; width:950px;">

		<div class="uc_title" name="showButton">
			<label class="current"><span>商品详情</span></label>
			<label id='point'><span>顾客评价(<?php echo isset($comments)?$comments:"";?>)</span></label>
			<label id='buyHistory'><span>购买记录(<?php echo isset($buy_num)?$buy_num:"";?>)</span></label>
			<label><span>购买前咨询(<?php echo isset($refer)?$refer:"";?>)</span></label>
		</div>

		<div name="showBox">
			<!-- 商品详情 start -->
			<div>
				<ul class="saleinfos m_10 clearfix">
					<li>商品名称：<?php echo isset($name)?$name:"";?></li>

					<?php if(isset($brand) && $brand){?>
					<li>品牌：<?php echo isset($brand)?$brand:"";?></li>
					<?php }?>
					
					<?php if(isset($weight) && $weight){?>
					<li>商品毛重：<label id="data_weight"><?php echo isset($weight)?$weight:"";?></label></li>
					<?php }?>
					
					<?php if(isset($unit) && $unit){?>
					<li>单位：<?php echo isset($unit)?$unit:"";?></li>
					<?php }?>

					<?php if(isset($up_time) && $up_time){?>
					<li>上架时间：<?php echo isset($up_time)?$up_time:"";?></li>
					<?php }?>
					<?php if(($attribute)){?>
					<?php foreach($attribute as $key => $item){?>
					<li><?php echo isset($item['name'])?$item['name']:"";?>：<?php echo isset($item['attribute_value'])?$item['attribute_value']:"";?></li>
					<?php }?>
					<?php }?>
					
				</ul>
				<?php if(isset($content) && $content){?>
				<div class="salebox">
					<strong class="saletitle block">产品描述：</strong>
					<p class="saledesc"><?php echo isset($content)?$content:"";?></p>
				</div>
				<?php }?>
			</div>
			<!-- 商品详情 end -->

			<!-- 顾客评论 start -->
			<div class="hidden comment_list box">
				<div class="title3">
					<span class="f_r f12 light_gray normal">
						只有购买过该商品的用户才能进行评价
						<?php if(isset($this->user['user_id']) && $user_id = $this->user['user_id']){?>
						<?php foreach(Api::run('getCommentByGoodsid',array('#id#',$id),array('#user_id#',$user_id),1) as $key => $item){?>
						<a class="comm_btn" href="<?php echo IUrl::creatUrl("/site/comments/id/".$item['id']."");?>">我要评论</a>
						<?php }?>
						<?php }?>
					</span>
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/comm.gif";?>" width="16px" height="16px" />商品评论<span class="f12 normal">（已有<b class="red2"><?php echo isset($comments)?$comments:"";?></b>条）</span>
				</div>

				<div id='commentBox'></div>

				<!--评论JS模板-->
				<script type='text/html' id='commentRowTemplate'>
				<div class="item">
					<div class="user">
						<div class="ico">
							<a href="javascript:void(0)">
								<img src="<?php echo IUrl::creatUrl("")."<%=head_ico%>";?>" width="70px" height="70px" onerror="this.src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/user_ico.gif";?>'" />
							</a>
						</div>
						<span class="blue"><%=username%></span>
					</div>
					<dl class="desc">
						<% var widthPoint = 14 * point;%>
						<p class="clearfix">
							<b>评分：</b>
							<span class="grade"><i style="width:<%=widthPoint%>px"></i></span>
							<span class="light_gray"><%=comment_time%></span><label></label>
						</p>
						<hr />
						<p><b>评价：</b><span class="gray"><%=contents%></span></p>
						<%if(recontents){%>
						<p><b>回复：</b><span class="red"><%=recontents%></span></p>
						<%}%>
					</dl>
					<div class="corner b"></div>
				</div>
				<hr />
				</script>
			</div>
			<!-- 顾客评论 end -->

			<!-- 购买记录 start -->
			<div class="hidden box" >
				<div class="title3" >
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/cart.gif";?>" width="16" height="16" alt="" />
					购买记录<span class="f12 normal">（已有<b class="red2"><?php echo isset($buy_num)?$buy_num:"";?></b>购买）</span>
				</div>

				<table width="100%" class="list_table m_10 mt_10">
					<col width="150" />
					<col width="120" />
					<col width="120" />
					<col width="150" />
					<col />
					<thead class="thead">
						<tr>
							<th>购买人</th>
							<th>出价</th>
							<th>数量</th>
							<th>购买时间</th>
							<th>状态</th>
						</tr>
					</thead>
				</table>

				<table width="100%" class="list_table m_10">
					<col width="150" />
					<col width="120" />
					<col width="120" />
					<col width="150" />
					<col />
					<tbody class="dashed" id="historyBox"></tbody>

					<!--购买历史js模板-->
					<script type='text/html' id='historyRowTemplate'>
					<tr>
						<td><%=username?username:'游客'%></td>
						<td><%=goods_price%></td>
						<td class="bold orange"><%=goods_nums%></td>
						<td class="light_gray"><%=completion_time%></td>
						<td class="bold blue">成交</td>
					</tr>
					</script>
				</table>
			</div>
			<!-- 购买记录 end -->

			<!-- 购买前咨询 start -->
			<div class="hidden comment_list box">
				<div class="title3">
					<span class="f_r f12 normal"><a class="comm_btn" href="<?php echo IUrl::creatUrl("/site/consult/id/".$id."");?>">我要咨询</a></span>
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/cart.gif";?>" width="16" height="16" />购买前咨询<span class="f12 normal">（共<b class="red2"><?php echo isset($refer)?$refer:"";?></b>记录）</span>
				</div>

				<div id='referBox'></div>

				<!--购买咨询JS模板-->
				<script type='text/html' id='referRowTemplate'>
				<div class="item">
					<div class="user">
						<div class="ico"><img src="<?php echo IUrl::creatUrl("")."<%=head_ico%>";?>" width="70px" height="70px" onerror="this.src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/user_ico.gif";?>'" /></div>
						<span class="blue"><%=username%></span>
						<p class="gray"><%=rtime%></p>
					</div>
					<dl class="desc gray">
						<p>
							<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/ask.gif";?>" width="16px" height="17px" />
							<b>咨询内容：</b><span class="f_r"><%=time%></span>
						</p>
						<p class="indent"><%=question%></p>
						<hr />
						<%if(answer){%>
						<p class="bg_gray"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/answer.gif";?>" width="16px" height="17px" />
						<b class="orange"><?php echo $siteConfig->name;?>回复：</b><span class="f_r"><%=reply_time%></span></p>
						<p class="indent bg_gray"><%=answer%></p>
						<%}%>
					</dl>
					<div class="corner b"></div>
					<div class="corner tl"></div>
				</div>
				<hr />
				</script>
			</div>
			<!-- 购买前咨询 end -->

			
		</div>
	</div>
</div>

<script type="text/javascript">
$(function(){

//图片初始化
var goodsSmallPic = "<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>";
var goodsBigPic   = "<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>";

//存在图片数据时候
<?php if(isset($photo) && $photo){?>
goodsSmallPic = "<?php echo IUrl::creatUrl("")."";?><?php echo Thumb::get($photo[0]['img'],435,435);?>";
goodsBigPic   = "<?php echo IUrl::creatUrl("")."";?><?php echo isset($photo[0]['img'])?$photo[0]['img']:"";?>";
<?php }?>
$('#J_ImgBooth').attr('src',goodsSmallPic);
$('#detShow img').attr('src',goodsBigPic);




//开启倒计时功能
var cd_timer = new countdown();



//团购倒计时
<?php if(isset($regiment)){?>
cd_timer.add('promotionb');
<?php }?>

//城市地域选择按钮事件
$('.sel_area').hover(
	function(){
		$('.area_box').show();
	},function(){
		$('.area_box').hide();
	}
);
$('.area_box').hover(
	function(){
		$('.area_box').show();
	},function(){
		$('.area_box').hide();
	}
);

//获取地址的ip地址
getAddress();

//生成商品价格
var priceHtml = template.render('priceTemplate',{"group_price":"<?php echo isset($group_price)?$group_price:"";?>","minSellPrice":"<?php echo isset($minSellPrice)?$minSellPrice:"";?>","maxSellPrice":"<?php echo isset($maxSellPrice)?$maxSellPrice:"";?>","sell_price":"<?php echo isset($sell_price)?$sell_price:"";?>"});
$('#price_panel').html(priceHtml);


//按钮绑定
$('[name="showButton"]>label').click(function(){
	$(this).siblings().removeClass('current');
	if($(this).hasClass('current') == false)
	{
		$(this).addClass('current');
	}
	$('[name="showBox"]>div').addClass('hidden');
	$('[name="showBox"]>div:eq('+$(this).index()+')').removeClass('hidden');

	switch($(this).index())
	{
		case 1:
		{
			comment_ajax();
		}
		break;

		case 2:
		{
			history_ajax();
		}
		break;

		case 3:
		{
			refer_ajax();
		}
		break;

	}
});

});

//禁止购买
function closeBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').attr('disabled','disabled');
		$('#buyNowButton').addClass('disabled');
	}

	if($('#joinCarButton').length > 0)
	{
		$('#joinCarButton').attr('disabled','disabled');
		$('#joinCarButton').addClass('disabled');
	}
}

//开放购买
function openBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').removeAttr('disabled');
		$('#buyNowButton').removeClass('disabled');
	}

	if($('#joinCarButton').length > 0)
	{
		$('#joinCarButton').removeAttr('disabled');
		$('#joinCarButton').removeClass('disabled');
	}
}

//加载根据地域获取城市
function getAddress()
{
	//根据IP查询所在地
	var ipAddress = $.cookie('ipAddress');
	if(ipAddress)
	{
		searchDelivery(ipAddress);
	}
	else
	{
		$.getScript('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js',function(){
			ipAddress = remote_ip_info['province'];
			$.cookie('ipAddress',ipAddress);
			searchDelivery(ipAddress);
		});
	}
}



/**
 * 根据省份获取运费信息
 * @param province 省份名称
 */
function searchDelivery(province)
{
	var url = '<?php echo IUrl::creatUrl("/block/searchPrivice/random/@random@");?>';
	url = url.replace("@random@",Math.random);

	$.getJSON(url,{'province':province},function(json)
	{
		if(json.flag == 'success')
		{
			delivery(json.area_id,province);
		}
	});
}

/**
 * 计算运费
 * @param provinceId
 * @param provinceName
 */
function delivery(provinceId,provinceName)
{
	$('.sel_area').text(provinceName);

	var buyNums = $('#buyNums').val();
	var productId = $('#product_id').val();
	var goodsId = "<?php echo isset($id)?$id:"";?>";

	//通过省份id查询出配送方式，并且传送总重量计算出运费,然后显示配送方式
	var url = '<?php echo IUrl::creatUrl("/block/order_delivery");?>';
	$.getJSON(url,{'province':provinceId,'goodsId':goodsId,'productId':productId,'num':buyNums,'random':Math.random},function(json)
	{
		//清空配送信息
		$('#deliveInfo').empty();

		for(var item in json)
		{
			var deliveRowHtml = template.render('deliveInfoTemplate',json[item]);
			$('#deliveInfo').append(deliveRowHtml);
		}
	});
}

/**
 * 获取评论数据
 * @page 分页数
 */
function comment_ajax(page)
{
	if(!page && $.trim($('#commentBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/comment_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空评论数据
		$('#commentBox').empty();

		for(var item in json.data)
		{
			var commentHtml = template.render('commentRowTemplate',json.data[item]);
			$('#commentBox').append(commentHtml);
		}
		$('#commentBox').append(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function history_ajax(page)
{
	if(!page && $.trim($('#historyBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/history_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空购买历史记录
		$('#historyBox').empty();
		$('#historyBox').parent().parent().find('.pages_bar').remove();

		for(var item in json.data)
		{
			var historyHtml = template.render('historyRowTemplate',json.data[item]);
			$('#historyBox').append(historyHtml);
		}
		$('#historyBox').parent().after(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function refer_ajax(page)
{
	if(!page && $.trim($('#referBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/refer_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空评论数据
		$('#referBox').empty();

		for(var item in json.data)
		{
			var commentHtml = template.render('referRowTemplate',json.data[item]);
			$('#referBox').append(commentHtml);
		}
		$('#referBox').append(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function discuss_ajax(page)
{
	if(!page && $.trim($('#discussBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/discuss_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空购买历史记录
		$('#discussBox').empty();
		$('#discussBox').parent().parent().find('.pages_bar').remove();

		for(var item in json.data)
		{
			var historyHtml = template.render('discussRowTemplate',json.data[item]);
			$('#discussBox').append(historyHtml);
		}
		$('#discussBox').parent().after(json.pageHtml);
	});
}



/**
 * 规格的选择
 * @param _self 规格本身
 */
function sele_spec(_self)
{
	var specObj = $.parseJSON($(_self).attr('value'));

	

	//已经为选中状态时
	if($(_self).attr('class') == 'current')
	{
		$(_self).removeClass('current');
	}
	else
	{
		//清除同行中其余规格选中状态
		$('#specList'+specObj.id).find('a.current').removeClass('current');

		$(_self).addClass('current');
	}

	//检查规格是否选择符合标准
	if(checkSpecSelected())
	{
		//整理规格值
		var specArray = [];
		$('[name="specCols"]').each(function(){
			specArray.push($(this).find('a.current').attr('value'));
		});
		var specJSON = '['+specArray.join(",")+']';

		//获取货品数据并进行渲染
		$.getJSON('<?php echo IUrl::creatUrl("/site/getProduct");?>',{"goods_id":"<?php echo isset($id)?$id:"";?>","specJSON":specJSON,"random":Math.random},function(json){
			//window.realAlert(JSON.stringify(json));
		//	window.realAlert(typeof(json.data.group_price));
			//var priceData = json.data;
			
			if(json.flag == 'success')
			{
				var priceHtml = template.render('priceTemplate',json.data);
				$('#price_panel').html(priceHtml);
				if(json.data.active_id && json.data.group_price){
					if(json.data.group_price>json.data.shan_price){
						$('input[name=active_id]').val(json.data.active_id);
						$('input[name=promo_name]').val('time');
					}else{
						$('input[name=active_id]').val('');
						$('input[name=promo_name]').val('');
					}
				}else if(json.data.active_id){
					$('input[name=active_id]').val(json.data.active_id);
					$('input[name=promo_name]').val('time');
				}
				else{
					$('input[name=active_id]').val('');
					$('input[name=promo_name]').val('');
				}
			
				//普通货品数据渲染
				$('#data_goodsNo').text(json.data.products_no);
				$('#data_storeNums').text(json.data.store_nums);
				$('#product_id').val(json.data.id);

				//库存监测
				checkStoreNums();
			}
			else
			{
				alert(json.message);
				closeBuy();
			}
		});
	}
}

/**
 * 监测库存操作
 */
function checkStoreNums()
{
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(storeNums > 0)
	{
		openBuy();
	}
	else
	{
		closeBuy();
	}
}

/**
 * 检查规格选择是否符合标准
 * @return boolen
 */
function checkSpecSelected()
{
	if($('[name="specCols"]').length === $('[name="specCols"] .current').length)
	{
		return true;
	}
	return false;
}

//检查购买数量是否合法
function checkBuyNums()
{
	//购买数量小于0
	var buyNums = parseInt($.trim($('#buyNums').val()));
	if(buyNums <= 0)
	{
		$('#buyNums').val(1);
		return;
	}

	//购买数量大于库存
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(buyNums >= storeNums)
	{
		$('#buyNums').val(storeNums);
		return;
	}
}

/**
 * 购物车数量的加减
 * @param code 增加或者减少购买的商品数量
 */
function modified(code)
{
	var buyNums = parseInt($.trim($('#buyNums').val()));
	switch(code)
	{
		case 1:
		{
			buyNums++;
		}
		break;

		case -1:
		{
			buyNums--;
		}
		break;
	}

	$('#buyNums').val(buyNums);
	checkBuyNums();
}

//商品加入购物车
function joinCart()
{
	if(!checkSpecSelected())
	{
		tips('请先选择商品的规格');
		return;
	}

	var buyNums   = parseInt($.trim($('#buyNums').val()));
	var price     = parseFloat($.trim($('#real_price').text()));
	var productId = $('#product_id').val();
	var type      = productId ? 'product' : 'goods';
	var goods_id  = (type == 'product') ? productId : <?php echo isset($id)?$id:"";?>;

	$.getJSON('<?php echo IUrl::creatUrl("/simple/joinCart");?>',{"goods_id":goods_id,"type":type,"goods_num":buyNums,"random":Math.random},function(content){
		
		if(content.isError == false)
		{
			//获取购物车信息
			$.getJSON('<?php echo IUrl::creatUrl("/simple/showCart");?>',{"random":Math.random},function(json)
			{//window.realAlert(JSON.stringify(json));
				$('[name="mycart_count"]').text(json.count);
				$('[name="mycart_sum"]').text(json.sum);

				//展示购物车清单
				$('#product_myCart').show();

				//暂闭加入购物车按钮
				$('#joinCarButton').attr('disabled','disabled');
			});
		}
		else
		{
			alert(content.message);
		}
	});
}

//添加收藏夹
function favorite_add(obj)
{
	<?php if(isset($this->user['user_id'])){?>
		$.getJSON('<?php echo IUrl::creatUrl("/simple/favorite_add");?>',{'goods_id':<?php echo isset($id)?$id:"";?>,'random':Math.random},function(content)
		{
			if(content.isError == false)
			{
				$(obj).text(content.message);
			}
			else
			{
				alert(content.message);
			}
		});
	<?php }else{?>
		window.location.href="<?php echo IUrl::creatUrl("/simple/login/?callback=/site/products/id/".$id."");?>";
	<?php }?>
}

//立即购买按钮
function buy_now()
{
	//对规格的检查
	if(!checkSpecSelected())
	{
		tips('请选择商品的规格');
		return;
	}

	//设置必要参数
	var buyNums  = parseFloat($.trim($('#buyNums').val()));
	var id = <?php echo isset($id)?$id:"";?>;
	var type = 'goods';

	if($('#product_id').val())
	{
		id = $('#product_id').val();
		type = 'product';
	}
	var promo_type=$('input[name=promo_name]').val();
	var active_id = $('input[name=active_id]').val();
	//var url = '<?php echo IUrl::creatUrl("/simple/cart2/id/@id@/num/@buyNums@/type/@type@/promo/".$promo."/active_id/".$active_id."");?>';
	//url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	
	var url = '<?php echo IUrl::creatUrl("/simple/cart2/id/@id@/num/@buyNums@/type/@type@");?>';
	url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	if(promo_type && active_id){
		url += '/promo/'+promo_type+'/active_id/'+active_id; 
	}

	//页面跳转
	window.location.href = url;
}
</script>
</div>
<!--区分规格的闪购价格显示并初始化规格选择-->
<?php if(isset($product_spec)){?>
	<?php foreach($product_spec as $key => $item){?>
		<?php $product_spec[$key] = JSON::decode($item);?>
		<input type='text' name='product_spec' value='<?php echo isset($product_spec[$key]["value"])?$product_spec[$key]["value"]:"";?>'/>
	
	<?php }?>

<script type='text/javascript'>
$(function(){
	var spec_input = $('input[name=product_spec]');
	var spec_len = spec_input.length;
	var spec = [];
	for(var i=0;i<spec_len;i++){
		spec[i] = spec_input.eq(i).val();
		$('a[value*='+spec[i]+']').trigger('click');
	}
})
</script>
<?php }?>
	

</div>
<?php }else{?>
	<?php 
	$seo_data=array();
	$site_config=new Config('site_config');
	$seo_data['title']=$name."_".$site_config->name;
	$seo_data['keywords']=$keywords;
	$seo_data['description']=$description;
	seo::set($seo_data);
	
?>

<script type="text/javascript" charset="UTF-8" src="/xinde/scsg/runtime/_systemjs/cookie/jquery.cookie.js"></script>
<?php $breadGuide = goods_class::catRecursion($category);$promotion=Api::run('getProrule');?>
<?php foreach($promotion as $key => $item){?>
	<?php if($item['type']==6){?><!--获取免配送费的信息-->
	<?php $freightInfo=$item['info']?>
	<?php }?>
<?php }?>
<link rel="stylesheet" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/index1.css";?>">
<link rel="stylesheet" type='text/css' href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/css/detail.css";?>"/>
<link rel="stylesheet" type="text/css" href="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/jquery.bxSlider/jquery.bxslider.css";?>" />
<script type="text/javascript" src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/jquery.bxSlider/jquery.bxSlider.min.js";?>"></script>
<script type='text/javascript' src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/javascript/piczoom.js";?>"></script>
<div class="position"><span>您当前的位置：</span><a href="<?php echo IUrl::creatUrl("");?>">首页</a><?php foreach($breadGuide as $key => $item){?> » <a href="<?php echo IUrl::creatUrl("/site/pro_list/cat/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a><?php }?> » <?php echo isset($name)?$name:"";?></div>
<div class="wrapper clearfix">
	
	<div class="summary" style='margin-right:15px;'>

		<!--货品ID，当为商品时值为空-->
		<input type='hidden' id='product_id' alt='货品ID' value='' />
		<input type='hidden' name='promo_name' value="<?php echo isset($promo_data['promo_type'])?$promo_data['promo_type']:"";?>"/><!--活动类型（闪购。。)-->
		<input type='hidden' name='active_id' value="<?php echo isset($promo_data['active_id'])?$promo_data['active_id']:"";?>"/><!--活动id-->
		<!--基本信息区域-->
		<div id='detail'>
			<div id="J_DetailMeta" class="tm-detail-meta tm-clear">
			<div class="tb-wrap">
				<div class="tb-detail-hd">
					<h1 data-spm="1000983">	<?php echo isset($name)?$name:"";?> </h1>
					<h2 data-spm="1000983" style="color:#e05757;">	<?php echo isset($short_desc)?$short_desc:"";?> </h2>
					<p> </p>
				</div>
				<div class="tm-fcs-panel" id='price_panel'>
					
				</div>
				<script  type='text/html' id='priceTemplate'>
					<%if(shan_price && group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price" style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%if(shan_price<=group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">会员价</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price"  style='text-decoration:line-through;'><%=group_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">闪购价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price'><%=shan_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else{%>
							<dl id="J_PromoPrice" class="tm-promo-panel"  >
								<dt class="tb-metatit">闪购价</dt>
								<dd><em class="tm-yen">¥</em>
									<span class="tm-price" style='text-decoration:line-through;'><%=shan_price%></span>
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
							<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
								<dt class="tb-metatit">会员价</dt>
								<dd>
									<em class="tm-yen">¥</em>
									<span class="tm-price" id='real_price'><%=group_price%></span>
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
						<%}%>
						
					<%}else{%>
						<!---->
						<%if(shan_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price' style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">闪购价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" id='real_price'><%=shan_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else if(group_price){%>
						<dl id="J_PromoPrice" class="tm-promo-panel"  >
							<dt class="tb-metatit">原价格</dt>
							<dd><em class="tm-yen">¥</em>
								<span class="tm-price"  style='text-decoration:line-through;'><%=sell_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
							<dt class="tb-metatit">会员价</dt>
							<dd>
								<em class="tm-yen">¥</em>
								<span class="tm-price" ><%=group_price%></span>
								<div class="staticPromoTip"> </div>
							</dd>
						</dl>
						<%}else{%>
							<dl id="J_StrPriceModBox" class="tm-price-panel tm-price-cur">
								<dt class="tb-metatit">价格</dt>
								<dd>
									<em class="tm-yen">¥</em>
									<%if(minSellPrice != maxSellPrice){%>
										<span class="tm-price" id='real_price'><%=minSellPrice%>---</span>
										<em class="tm-yen">¥</em>
										<span class="tm-price" id='real_price'><%=maxSellPrice%></span>
									<%}else{%>
										<span class="tm-price" id='real_price'><%=sell_price%></span>
									<%}%>
									
									<div class="staticPromoTip"> </div>
								</dd>
							</dl>
						<%}%>
						
					<%}%>
				</script>
				<div class="tb-meta">
					<dl id="J_RSPostageCont" class="tm-delivery-panel">
						<dt class="tb-metatit">运费</dt>
						<dd>
							<div class="tb-postAge" style='color:#666;'>
								<?php echo isset($freightInfo)?$freightInfo:"";?>
								<a href="<?php echo IUrl::creatUrl("/site/help/id/73");?>" style='color:blue;'>配送说明</a>
							</div>
						</dd>
					</dl>
				</div>
				<div class="tb-meta">
					<dl id="J_RSPostageCont" class="tm-delivery-panel">
						<dt class="tb-metatit">服务</dt>
						<dd>
							<div class="tb-postAge">
							由<?php if(isset($seller)){?>
							<?php echo isset($seller['true_name'])?$seller['true_name']:"";?>
							<?php }else{?>
							山城速购
							<?php }?>
							配送,并提供售后服务
							</div>
						</dd>
					</dl>
				</div>
					<ul class="tm-ind-panel">
						<li class="tm-ind-item tm-ind-sellCount canClick" >
							<div class="tm-indcon" onclick="jumpTo('buyHistory')">
									<span class="tm-label">销售记录</span>
								<span class="tm-count"><?php echo isset($buy_num)?$buy_num:"";?></span>
							</div>
						</li>
						<li id="J_ItemRates" class="tm-ind-item tm-ind-reviewCount canClick tm-line3">
							<div class="tm-indcon"  onclick="jumpTo('point')">
								<span class="tm-label">累计评价</span>
								<span class="tm-count"><?php echo isset($comments)?$comments:"";?></span>
							</div>
						</li>
						<li class="tm-ind-item tm-ind-emPointCount" >
							<div class="tm-indcon">
								<a target="_blank" href="" >
									<span class="tm-label">送积分</span>
									<span class="tm-count"><?php echo isset($point)?$point:"";?></span>
								</a>
							</div>
						</li>
					</ul>
					<div class="tb-key">
						<div class="tb-skin">
							<div class="tb-sku">
								
								<?php if($spec_array){?>
								<?php $specArray = JSON::decode($spec_array);?>
								<?php foreach($specArray as $key => $item){?>
								<dl class="m_10 clearfix" name="specCols">
									<dt><?php echo isset($item['name'])?$item['name']:"";?></dt>
									<dd class="w_45" style="margin-left:67px;" id="specList<?php echo isset($item['id'])?$item['id']:"";?>">
										<?php $specVal=explode(',',trim($item['value'],','))?>
										<?php foreach($specVal as $key => $spec_value){?>
										<?php if($item['type'] == 1){?>
										<div class="item w_27"><a href="javascript:void(0);" onclick="sele_spec(this);" value='{"id":"<?php echo isset($item['id'])?$item['id']:"";?>","type":"<?php echo isset($item['type'])?$item['type']:"";?>","value":"<?php echo isset($spec_value)?$spec_value:"";?>","name":"<?php echo isset($item['name'])?$item['name']:"";?>"}' ><?php echo isset($spec_value)?$spec_value:"";?><span></span></a></div>
										<?php }else{?>
										<div class="item"><a href="javascript:void(0);" onclick="sele_spec(this);" value='{"id":"<?php echo isset($item['id'])?$item['id']:"";?>","type":"<?php echo isset($item['type'])?$item['type']:"";?>","value":"<?php echo isset($spec_value)?$spec_value:"";?>","name":"<?php echo isset($item['name'])?$item['name']:"";?>"}' ><img src="<?php echo IUrl::creatUrl("")."".$spec_value."";?>" width='30px' height='30px' /><span></span></a></div>
										<?php }?>
										<?php }?>
									</dd>
								</dl>
								<?php }?>
								<?php }?>
																								
								<dl class="tb-amount tm-clear">
									<dt class="tb-metatit">数量</dt>
									<dd id="J_Amount">
										<input class="gray_t f_l" type="text" id="buyNums" onblur="checkBuyNums();" value="1" maxlength="5" />
											<div class="resize">
												<a class="add" href="javascript:modified(1);"></a>
												<a class="reduce" href="javascript:modified(-1);"></a>
											</div>
											<span class="mui-amount-unit">&nbsp;件</span>
										<em id="J_EmStock" class="tb-hidden" style="display: inline;">库存<label id="data_storeNums"><?php echo isset($store_nums)?$store_nums:"";?></label>件</em>
										<span id="J_StockTips"> </span>
									</dd>
								</dl>
							</div>
						</div>
					</div>
					
					
				</div>
			</div>
		</div>

		<!--购买区域-->
		<div class="current">
			<?php if($store_nums <= 0){?>
				该商品已售完，不能购买，您可以看看其它商品！(<a href="<?php echo IUrl::creatUrl("/simple/arrival/goods_id/".$id."");?>" class="orange">到货通知</a>)
			<?php }else{?>

			<input class="submit_buy" type="button" id="buyNowButton" onclick="buy_now();" value="立即购买" />

						<div class="shop_cart" style="z-index:1">
				<input class="submit_join" type="button" id="joinCarButton" onclick="joinCart();" value="加入购物车" />

				<div class="shopping" id="product_myCart" style='display:none'>
					<dl class="cart_stats">
						<dt class="gray f14 bold">
							<a class="close_2 f_r" href="javascript:closeCartDiv();" title="关闭">关闭</a>
							<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/right_s.gif";?>" width="24" height="24" alt="" />成功加入购物车
						</dt>
						<dd class="gray">目前选购商品共<b class="orange" name='mycart_count'></b>件<span>合计：<b name='mycart_sum'></b></span></dd>
						<dd><a class="btn_blue bold" href="<?php echo IUrl::creatUrl("/simple/cart");?>">进入购物车</a><a class="btn_blue bold" href="javascript:void(0)" onclick="closeCartDiv();">继续购物>></a></dd>
					</dl>
				</div>
			</div>
			<?php }?>
		</div>
		
		<div class="tm-ser tm-clear">
			<dl class="tm-clear">
				<dt class="tb-metatit">服务承诺</dt>
				<dd class="tm-laysku-dd">
				<ul class="tb-serPromise">
					<li>
						<a target="_blank" title="按时发货" href=""> 按时发货 </a>
					</li>
					<li>
						<a target="_blank" title="七天无理由退还" href=""> 七天无理由退还 </a>
					</li>
					<li>
						<a target="_blank" title="品质保障" href=""> 品质保障 </a>
					</li>
				</ul>
			</dl>
		</div>

	</div>
		<!--图片放大器-->
		<div class="tb-gallery">
		<div class="tb-booth" id='bigPic'>
			<?php if($tag_data){?>
				<?php foreach($tag_data as $key => $item){?>
				<?php $right=$key*60?>
			 		<img src="<?php echo IUrl::creatUrl("")."".$item['img']."";?>" style='right:<?php echo isset($right)?$right:"";?>px' class="mark">
				<?php }?>
			<?php }?>
				
			<a target="_blank" rel="nofollow" href="" >
			<span class="ks-imagezoom-wrap">		
			
				<img id="J_ImgBooth"  name="bigpro"  src="" style='cursor:crosshair;'>
				<span class="ks-imagezoom-lens" style="position: absolute; border:1px solid blue;left:1px;"></span>
			</span>
			</a>
		</div>
		<div id='detShow' class="ks-overlay ks-imagezoom-viewer" style="width: 435px; height: 435px; left: 480px; top: 20px;display:none;">
			<div id="detPic" class="ks-overlay-content">
				<img style="position: absolute; top: 0px; left: 0px;" src="">
			</div>
		</div>
		<ul id="J_UlThumb" class="tb-thumb tm-clear">
			<?php if(count($photo)!=0){?>
			<?php foreach($photo as $key => $item){?>
			<li class="">
				<a href="#" onmouseover='showBig("<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/435/h/435");?>","<?php echo IUrl::creatUrl("")."".$item['img']."";?>")'>
					<img src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/60/h/60");?>">
				</a>
			</li>
			<?php }?>
			<?php }else{?>
				<li class="">
					<a href="#" onclick='javascript:void(0)'>
						<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>">
					</a>
				</li>
			<?php }?>
		</ul>
		<div style="margin-top:10px;margin-left:50px;">			
			<!-- JiaThis Button BEGIN -->
				<style>
					.bdshare_popup_box{z-index:100000000;}
					.bdshare_dialog_box{z-index:100000000;}
					.bd_weixin_popup_bg{z-index:100000000;}
					.bd_weixin_popup{z-index:100000000;}
				</style>

				
			<div class="bdsharebuttonbox">
				<a href="#" class="bds_more" data-cmd="more">分享到：</a>
				<a href="#" class="bds_tsina" data-cmd="tsina" title="分享到新浪微博">新浪微博</a>
			</div>
			<script>window._bd_share_config={"common":{"bdSnsKey":{},"bdText":"","bdMini":"2","bdMiniList":false,"bdPic":"","bdStyle":"0","bdSize":"16"},"share":{"bdSize":16}};with(document)0[(getElementsByTagName('head')[0]||body).appendChild(createElement('script')).src='http://bdimg.share.baidu.com/static/api/js/share.js?v=89860593.js?cdnversion='+~(-new Date()/36e5)];</script>

<!-- JiaThis Button END -->
			<?php $cls=isset($favorite_id_arr)&&in_array($id,$favorite_id_arr) ? 'guanzhu' : 'quguan'?>
				<div class='<?php echo isset($cls)?$cls:"";?>' style="line-height:26px;position:absolute; left:300px;bottom:1px; padding-left:17px; " id="gz"><a href='javascript:void(0)' onclick='favorite_add(this)'>我的关注：(<?php echo isset($favorite)?$favorite:"";?>人气)</a></div>
		</div>
	</div>

</div>
	
<script type='text/javascript'>
	$("#gz").click(function(){$(this).removeClass('quguan').addClass('guanzhu');});
</script>


<div class="wrapper clearfix container_2">

	<!--左边栏-->
	<div class="sidebar f_l">
		<?php if(isset($seller)){?>
		<!--商家-->
		<div class="box m_10">
			<div class="title"><a target='blank' href='<?php echo IUrl::creatUrl("/site/home/id/".$seller['id']."");?>'><?php echo isset($seller['true_name'])?$seller['true_name']:"";?></a></div>
			<div class="cont">
				<ul class="list">
					<?php $ave = statistics::getSellerGrade($seller['id']);$ave = $ave*70/5;?>
					<li style='background:none;'>评分：<span class="grade"><i style="width:<?php echo isset($ave)?$ave:"";?>px"></i></span></li>
					<li style='background:none;'>客服QQ:
						<a target="_blank" href="<?php echo Sonline::getChatUrl($seller['server_num']);?>" >
							<img border="0"  src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/qq.jpg";?>"/>
						</a>
					</li>
					<li style='background:none;'>
					<a class="btn_blue bold" href="<?php echo IUrl::creatUrl("/site/home/id/".$seller['id']."");?>">进入店铺</a>
					</li>
				</ul>
			</div>
		</div>
		<?php }?>
				<!--促销规则-->
		<div class="box m_10">
			<div class="title">促销活动</div>
			<div class="cont">
				<ul class="list">
				<?php foreach($promotion as $key => $item){?>
					<li><?php echo isset($item['info'])?$item['info']:"";?></li>
				<?php }?>
				</ul>
			</div>
		</div>
		<!--促销规则-->

		<!--购买推荐-->
		<?php if(isset($buyer_id) && $buyer_id){?>
				<div class="box m_10">
			<div class="title">购买本商品的用户还购买过</div>
			<div class="content" style='width:100%;'>
				<ul class="ranklist">
					<?php foreach(Api::run('getOrderGoodsByBuyerid',array('#buyer_id#',$buyer_id)) as $key => $item){?>
					<li class="current">
						<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><img width="58px" height="58px" src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/58/h/58");?>"></a>
						<a title="<?php echo isset($item['name'])?$item['name']:"";?>" class="p_name" href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a>
						<b>￥<?php echo isset($item['price'])?$item['price']:"";?></b>
					</li>
					<?php }?>
				</ul>
			</div>
		</div>
		<?php }?>
				<!--购买推荐-->

		<!--热卖商品-->
		<div class="box m_10">
			<div class="title">热卖商品</div>
			<div class="content" style='width:100%;'>
				<ul class="ranklist">
					<?php foreach(Api::run('getCommendHot') as $key => $item){?>
									<li class="current">
						<a href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><img width="58px" height="58px" alt="苹果" src="<?php echo IUrl::creatUrl("/pic/thumb/img/".$item['img']."/w/58/h/58");?>" /></a>
						<a title="<?php echo isset($item['name'])?$item['name']:"";?>" class="p_name" href="<?php echo IUrl::creatUrl("/site/products/id/".$item['id']."");?>"><?php echo isset($item['name'])?$item['name']:"";?></a>
						<b>￥<?php echo isset($item['sell_price'])?$item['sell_price']:"";?></b>
					</li>
					<?php }?>
				</ul>
			</div>
		</div>
		<!--热卖商品-->
	</div>

	<!--滑动面tab标签-->
	<div class="main f_r" style="overflow:hidden; width:950px;">

		<div class="uc_title" name="showButton">
			<label class="current"><span>商品详情</span></label>
			<label id='point'><span>顾客评价(<?php echo isset($comments)?$comments:"";?>)</span></label>
			<label id='buyHistory'><span>购买记录(<?php echo isset($buy_num)?$buy_num:"";?>)</span></label>
			<label><span>购买前咨询(<?php echo isset($refer)?$refer:"";?>)</span></label>
		</div>

		<div name="showBox">
			<!-- 商品详情 start -->
			<div>
				<ul class="saleinfos m_10 clearfix">
					<li>商品名称：<?php echo isset($name)?$name:"";?></li>

					<?php if(isset($brand) && $brand){?>
					<li>品牌：<?php echo isset($brand)?$brand:"";?></li>
					<?php }?>
					
					<?php if(isset($weight) && $weight){?>
					<li>商品毛重：<label id="data_weight"><?php echo isset($weight)?$weight:"";?></label></li>
					<?php }?>
					
					<?php if(isset($unit) && $unit){?>
					<li>单位：<?php echo isset($unit)?$unit:"";?></li>
					<?php }?>

					<?php if(isset($up_time) && $up_time){?>
					<li>上架时间：<?php echo isset($up_time)?$up_time:"";?></li>
					<?php }?>
					<?php if(($attribute)){?>
					<?php foreach($attribute as $key => $item){?>
					<li><?php echo isset($item['name'])?$item['name']:"";?>：<?php echo isset($item['attribute_value'])?$item['attribute_value']:"";?></li>
					<?php }?>
					<?php }?>
					
				</ul>
				<?php if(isset($content) && $content){?>
				<div class="salebox">
					<strong class="saletitle block">产品描述：</strong>
					<p class="saledesc"><?php echo isset($content)?$content:"";?></p>
				</div>
				<?php }?>
			</div>
			<!-- 商品详情 end -->

			<!-- 顾客评论 start -->
			<div class="hidden comment_list box">
				<div class="title3">
					<span class="f_r f12 light_gray normal">
						只有购买过该商品的用户才能进行评价
						<?php if(isset($this->user['user_id']) && $user_id = $this->user['user_id']){?>
						<?php foreach(Api::run('getCommentByGoodsid',array('#id#',$id),array('#user_id#',$user_id),1) as $key => $item){?>
						<a class="comm_btn" href="<?php echo IUrl::creatUrl("/site/comments/id/".$item['id']."");?>">我要评论</a>
						<?php }?>
						<?php }?>
					</span>
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/comm.gif";?>" width="16px" height="16px" />商品评论<span class="f12 normal">（已有<b class="red2"><?php echo isset($comments)?$comments:"";?></b>条）</span>
				</div>

				<div id='commentBox'></div>

				<!--评论JS模板-->
				<script type='text/html' id='commentRowTemplate'>
				<div class="item">
					<div class="user">
						<div class="ico">
							<a href="javascript:void(0)">
								<img src="<?php echo IUrl::creatUrl("")."<%=head_ico%>";?>" width="70px" height="70px" onerror="this.src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/user_ico.gif";?>'" />
							</a>
						</div>
						<span class="blue"><%=username%></span>
					</div>
					<dl class="desc">
						<% var widthPoint = 14 * point;%>
						<p class="clearfix">
							<b>评分：</b>
							<span class="grade"><i style="width:<%=widthPoint%>px"></i></span>
							<span class="light_gray"><%=comment_time%></span><label></label>
						</p>
						<hr />
						<p><b>评价：</b><span class="gray"><%=contents%></span></p>
						<%if(recontents){%>
						<p><b>回复：</b><span class="red"><%=recontents%></span></p>
						<%}%>
					</dl>
					<div class="corner b"></div>
				</div>
				<hr />
				</script>
			</div>
			<!-- 顾客评论 end -->

			<!-- 购买记录 start -->
			<div class="hidden box" >
				<div class="title3" >
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/cart.gif";?>" width="16" height="16" alt="" />
					购买记录<span class="f12 normal">（已有<b class="red2"><?php echo isset($buy_num)?$buy_num:"";?></b>购买）</span>
				</div>

				<table width="100%" class="list_table m_10 mt_10">
					<col width="150" />
					<col width="120" />
					<col width="120" />
					<col width="150" />
					<col />
					<thead class="thead">
						<tr>
							<th>购买人</th>
							<th>出价</th>
							<th>数量</th>
							<th>购买时间</th>
							<th>状态</th>
						</tr>
					</thead>
				</table>

				<table width="100%" class="list_table m_10">
					<col width="150" />
					<col width="120" />
					<col width="120" />
					<col width="150" />
					<col />
					<tbody class="dashed" id="historyBox"></tbody>

					<!--购买历史js模板-->
					<script type='text/html' id='historyRowTemplate'>
					<tr>
						<td><%=username?username:'游客'%></td>
						<td><%=goods_price%></td>
						<td class="bold orange"><%=goods_nums%></td>
						<td class="light_gray"><%=completion_time%></td>
						<td class="bold blue">成交</td>
					</tr>
					</script>
				</table>
			</div>
			<!-- 购买记录 end -->

			<!-- 购买前咨询 start -->
			<div class="hidden comment_list box">
				<div class="title3">
					<span class="f_r f12 normal"><a class="comm_btn" href="<?php echo IUrl::creatUrl("/site/consult/id/".$id."");?>">我要咨询</a></span>
					<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/cart.gif";?>" width="16" height="16" />购买前咨询<span class="f12 normal">（共<b class="red2"><?php echo isset($refer)?$refer:"";?></b>记录）</span>
				</div>

				<div id='referBox'></div>

				<!--购买咨询JS模板-->
				<script type='text/html' id='referRowTemplate'>
				<div class="item">
					<div class="user">
						<div class="ico"><img src="<?php echo IUrl::creatUrl("")."<%=head_ico%>";?>" width="70px" height="70px" onerror="this.src='<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/user_ico.gif";?>'" /></div>
						<span class="blue"><%=username%></span>
						<p class="gray"><%=rtime%></p>
					</div>
					<dl class="desc gray">
						<p>
							<img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/ask.gif";?>" width="16px" height="17px" />
							<b>咨询内容：</b><span class="f_r"><%=time%></span>
						</p>
						<p class="indent"><%=question%></p>
						<hr />
						<%if(answer){%>
						<p class="bg_gray"><img src="<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/answer.gif";?>" width="16px" height="17px" />
						<b class="orange"><?php echo $siteConfig->name;?>回复：</b><span class="f_r"><%=reply_time%></span></p>
						<p class="indent bg_gray"><%=answer%></p>
						<%}%>
					</dl>
					<div class="corner b"></div>
					<div class="corner tl"></div>
				</div>
				<hr />
				</script>
			</div>
			<!-- 购买前咨询 end -->

			
		</div>
	</div>
</div>

<script type="text/javascript">
$(function(){

//图片初始化
var goodsSmallPic = "<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>";
var goodsBigPic   = "<?php echo IUrl::creatUrl("")."views/".$this->theme."/skin/".$this->skin."/images/front/nopic_435_435.gif";?>";

//存在图片数据时候
<?php if(isset($photo) && $photo){?>
goodsSmallPic = "<?php echo IUrl::creatUrl("")."";?><?php echo Thumb::get($photo[0]['img'],435,435);?>";
goodsBigPic   = "<?php echo IUrl::creatUrl("")."";?><?php echo isset($photo[0]['img'])?$photo[0]['img']:"";?>";
<?php }?>
$('#J_ImgBooth').attr('src',goodsSmallPic);
$('#detShow img').attr('src',goodsBigPic);




//开启倒计时功能
var cd_timer = new countdown();



//团购倒计时
<?php if(isset($regiment)){?>
cd_timer.add('promotionb');
<?php }?>

//城市地域选择按钮事件
$('.sel_area').hover(
	function(){
		$('.area_box').show();
	},function(){
		$('.area_box').hide();
	}
);
$('.area_box').hover(
	function(){
		$('.area_box').show();
	},function(){
		$('.area_box').hide();
	}
);

//获取地址的ip地址
getAddress();

//生成商品价格
var priceHtml = template.render('priceTemplate',{"group_price":"<?php echo isset($group_price)?$group_price:"";?>","minSellPrice":"<?php echo isset($minSellPrice)?$minSellPrice:"";?>","maxSellPrice":"<?php echo isset($maxSellPrice)?$maxSellPrice:"";?>","sell_price":"<?php echo isset($sell_price)?$sell_price:"";?>"});
$('#price_panel').html(priceHtml);


//按钮绑定
$('[name="showButton"]>label').click(function(){
	$(this).siblings().removeClass('current');
	if($(this).hasClass('current') == false)
	{
		$(this).addClass('current');
	}
	$('[name="showBox"]>div').addClass('hidden');
	$('[name="showBox"]>div:eq('+$(this).index()+')').removeClass('hidden');

	switch($(this).index())
	{
		case 1:
		{
			comment_ajax();
		}
		break;

		case 2:
		{
			history_ajax();
		}
		break;

		case 3:
		{
			refer_ajax();
		}
		break;

	}
});

});

//禁止购买
function closeBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').attr('disabled','disabled');
		$('#buyNowButton').addClass('disabled');
	}

	if($('#joinCarButton').length > 0)
	{
		$('#joinCarButton').attr('disabled','disabled');
		$('#joinCarButton').addClass('disabled');
	}
}

//开放购买
function openBuy()
{
	if($('#buyNowButton').length > 0)
	{
		$('#buyNowButton').removeAttr('disabled');
		$('#buyNowButton').removeClass('disabled');
	}

	if($('#joinCarButton').length > 0)
	{
		$('#joinCarButton').removeAttr('disabled');
		$('#joinCarButton').removeClass('disabled');
	}
}

//加载根据地域获取城市
function getAddress()
{
	//根据IP查询所在地
	var ipAddress = $.cookie('ipAddress');
	if(ipAddress)
	{
		searchDelivery(ipAddress);
	}
	else
	{
		$.getScript('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js',function(){
			ipAddress = remote_ip_info['province'];
			$.cookie('ipAddress',ipAddress);
			searchDelivery(ipAddress);
		});
	}
}



/**
 * 根据省份获取运费信息
 * @param province 省份名称
 */
function searchDelivery(province)
{
	var url = '<?php echo IUrl::creatUrl("/block/searchPrivice/random/@random@");?>';
	url = url.replace("@random@",Math.random);

	$.getJSON(url,{'province':province},function(json)
	{
		if(json.flag == 'success')
		{
			delivery(json.area_id,province);
		}
	});
}

/**
 * 计算运费
 * @param provinceId
 * @param provinceName
 */
function delivery(provinceId,provinceName)
{
	$('.sel_area').text(provinceName);

	var buyNums = $('#buyNums').val();
	var productId = $('#product_id').val();
	var goodsId = "<?php echo isset($id)?$id:"";?>";

	//通过省份id查询出配送方式，并且传送总重量计算出运费,然后显示配送方式
	var url = '<?php echo IUrl::creatUrl("/block/order_delivery");?>';
	$.getJSON(url,{'province':provinceId,'goodsId':goodsId,'productId':productId,'num':buyNums,'random':Math.random},function(json)
	{
		//清空配送信息
		$('#deliveInfo').empty();

		for(var item in json)
		{
			var deliveRowHtml = template.render('deliveInfoTemplate',json[item]);
			$('#deliveInfo').append(deliveRowHtml);
		}
	});
}

/**
 * 获取评论数据
 * @page 分页数
 */
function comment_ajax(page)
{
	if(!page && $.trim($('#commentBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/comment_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空评论数据
		$('#commentBox').empty();

		for(var item in json.data)
		{
			var commentHtml = template.render('commentRowTemplate',json.data[item]);
			$('#commentBox').append(commentHtml);
		}
		$('#commentBox').append(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function history_ajax(page)
{
	if(!page && $.trim($('#historyBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/history_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空购买历史记录
		$('#historyBox').empty();
		$('#historyBox').parent().parent().find('.pages_bar').remove();

		for(var item in json.data)
		{
			var historyHtml = template.render('historyRowTemplate',json.data[item]);
			$('#historyBox').append(historyHtml);
		}
		$('#historyBox').parent().after(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function refer_ajax(page)
{
	if(!page && $.trim($('#referBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/refer_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空评论数据
		$('#referBox').empty();

		for(var item in json.data)
		{
			var commentHtml = template.render('referRowTemplate',json.data[item]);
			$('#referBox').append(commentHtml);
		}
		$('#referBox').append(json.pageHtml);
	});
}

/**
 * 获取购买记录数据
 * @page 分页数
 */
function discuss_ajax(page)
{
	if(!page && $.trim($('#discussBox').text()))
	{
		return;
	}
	page = page ? page : 1;
	var url = '<?php echo IUrl::creatUrl("/site/discuss_ajax/page/@page@/goods_id/".$id."");?>';
	url = url.replace("@page@",page);
	$.getJSON(url,function(json)
	{
		//清空购买历史记录
		$('#discussBox').empty();
		$('#discussBox').parent().parent().find('.pages_bar').remove();

		for(var item in json.data)
		{
			var historyHtml = template.render('discussRowTemplate',json.data[item]);
			$('#discussBox').append(historyHtml);
		}
		$('#discussBox').parent().after(json.pageHtml);
	});
}



/**
 * 规格的选择
 * @param _self 规格本身
 */
function sele_spec(_self)
{
	var specObj = $.parseJSON($(_self).attr('value'));

	

	//已经为选中状态时
	if($(_self).attr('class') == 'current')
	{
		$(_self).removeClass('current');
	}
	else
	{
		//清除同行中其余规格选中状态
		$('#specList'+specObj.id).find('a.current').removeClass('current');

		$(_self).addClass('current');
	}

	//检查规格是否选择符合标准
	if(checkSpecSelected())
	{
		//整理规格值
		var specArray = [];
		$('[name="specCols"]').each(function(){
			specArray.push($(this).find('a.current').attr('value'));
		});
		var specJSON = '['+specArray.join(",")+']';

		//获取货品数据并进行渲染
		$.getJSON('<?php echo IUrl::creatUrl("/site/getProduct");?>',{"goods_id":"<?php echo isset($id)?$id:"";?>","specJSON":specJSON,"random":Math.random},function(json){
			//window.realAlert(JSON.stringify(json));
		//	window.realAlert(typeof(json.data.group_price));
			//var priceData = json.data;
			
			if(json.flag == 'success')
			{
				var priceHtml = template.render('priceTemplate',json.data);
				$('#price_panel').html(priceHtml);
				if(json.data.active_id && json.data.group_price){
					if(json.data.group_price>json.data.shan_price){
						$('input[name=active_id]').val(json.data.active_id);
						$('input[name=promo_name]').val('time');
					}else{
						$('input[name=active_id]').val('');
						$('input[name=promo_name]').val('');
					}
				}else if(json.data.active_id){
					$('input[name=active_id]').val(json.data.active_id);
					$('input[name=promo_name]').val('time');
				}
				else{
					$('input[name=active_id]').val('');
					$('input[name=promo_name]').val('');
				}
			
				//普通货品数据渲染
				$('#data_goodsNo').text(json.data.products_no);
				$('#data_storeNums').text(json.data.store_nums);
				$('#product_id').val(json.data.id);

				//库存监测
				checkStoreNums();
			}
			else
			{
				alert(json.message);
				closeBuy();
			}
		});
	}
}

/**
 * 监测库存操作
 */
function checkStoreNums()
{
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(storeNums > 0)
	{
		openBuy();
	}
	else
	{
		closeBuy();
	}
}

/**
 * 检查规格选择是否符合标准
 * @return boolen
 */
function checkSpecSelected()
{
	if($('[name="specCols"]').length === $('[name="specCols"] .current').length)
	{
		return true;
	}
	return false;
}

//检查购买数量是否合法
function checkBuyNums()
{
	//购买数量小于0
	var buyNums = parseInt($.trim($('#buyNums').val()));
	if(buyNums <= 0)
	{
		$('#buyNums').val(1);
		return;
	}

	//购买数量大于库存
	var storeNums = parseInt($.trim($('#data_storeNums').text()));
	if(buyNums >= storeNums)
	{
		$('#buyNums').val(storeNums);
		return;
	}
}

/**
 * 购物车数量的加减
 * @param code 增加或者减少购买的商品数量
 */
function modified(code)
{
	var buyNums = parseInt($.trim($('#buyNums').val()));
	switch(code)
	{
		case 1:
		{
			buyNums++;
		}
		break;

		case -1:
		{
			buyNums--;
		}
		break;
	}

	$('#buyNums').val(buyNums);
	checkBuyNums();
}

//商品加入购物车
function joinCart()
{
	if(!checkSpecSelected())
	{
		tips('请先选择商品的规格');
		return;
	}

	var buyNums   = parseInt($.trim($('#buyNums').val()));
	var price     = parseFloat($.trim($('#real_price').text()));
	var productId = $('#product_id').val();
	var type      = productId ? 'product' : 'goods';
	var goods_id  = (type == 'product') ? productId : <?php echo isset($id)?$id:"";?>;

	$.getJSON('<?php echo IUrl::creatUrl("/simple/joinCart");?>',{"goods_id":goods_id,"type":type,"goods_num":buyNums,"random":Math.random},function(content){
		
		if(content.isError == false)
		{
			//获取购物车信息
			$.getJSON('<?php echo IUrl::creatUrl("/simple/showCart");?>',{"random":Math.random},function(json)
			{//window.realAlert(JSON.stringify(json));
				$('[name="mycart_count"]').text(json.count);
				$('[name="mycart_sum"]').text(json.sum);

				//展示购物车清单
				$('#product_myCart').show();

				//暂闭加入购物车按钮
				$('#joinCarButton').attr('disabled','disabled');
			});
		}
		else
		{
			alert(content.message);
		}
	});
}

//添加收藏夹
function favorite_add(obj)
{
	<?php if(isset($this->user['user_id'])){?>
		$.getJSON('<?php echo IUrl::creatUrl("/simple/favorite_add");?>',{'goods_id':<?php echo isset($id)?$id:"";?>,'random':Math.random},function(content)
		{
			if(content.isError == false)
			{
				$(obj).text(content.message);
			}
			else
			{
				alert(content.message);
			}
		});
	<?php }else{?>
		window.location.href="<?php echo IUrl::creatUrl("/simple/login/?callback=/site/products/id/".$id."");?>";
	<?php }?>
}

//立即购买按钮
function buy_now()
{
	//对规格的检查
	if(!checkSpecSelected())
	{
		tips('请选择商品的规格');
		return;
	}

	//设置必要参数
	var buyNums  = parseFloat($.trim($('#buyNums').val()));
	var id = <?php echo isset($id)?$id:"";?>;
	var type = 'goods';

	if($('#product_id').val())
	{
		id = $('#product_id').val();
		type = 'product';
	}
	var promo_type=$('input[name=promo_name]').val();
	var active_id = $('input[name=active_id]').val();
	//var url = '<?php echo IUrl::creatUrl("/simple/cart2/id/@id@/num/@buyNums@/type/@type@/promo/".$promo."/active_id/".$active_id."");?>';
	//url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	
	var url = '<?php echo IUrl::creatUrl("/simple/cart2/id/@id@/num/@buyNums@/type/@type@");?>';
	url = url.replace('@id@',id).replace('@buyNums@',buyNums).replace('@type@',type);
	if(promo_type && active_id){
		url += '/promo/'+promo_type+'/active_id/'+active_id; 
	}

	//页面跳转
	window.location.href = url;
}
</script>
</div>
<!--区分规格的闪购价格显示并初始化规格选择-->
<?php if(isset($product_spec)){?>
	<?php foreach($product_spec as $key => $item){?>
		<?php $product_spec[$key] = JSON::decode($item);?>
		<input type='text' name='product_spec' value='<?php echo isset($product_spec[$key]["value"])?$product_spec[$key]["value"]:"";?>'/>
	
	<?php }?>

<script type='text/javascript'>
$(function(){
	var spec_input = $('input[name=product_spec]');
	var spec_len = spec_input.length;
	var spec = [];
	for(var i=0;i<spec_len;i++){
		spec[i] = spec_input.eq(i).val();
		$('a[value*='+spec[i]+']').trigger('click');
	}
})
</script>
<?php }?>
	

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
	$.ajax({
		type:'post',
		async:true,
		data:{'id':id},
		dataType:'json',
		url:'<?php echo IUrl::creatUrl("/simple/getProducts");?>',
		success:function(content){
			if(!content)
			{
				joinCart_ajax(id,'<?php echo IUrl::creatUrl("/simple/joinCart");?>','goods');
			}
			else
			{
				var url = "<?php echo IUrl::creatUrl("/block/goods_list/goods_id/@goods_id@/type/radio/is_products/1");?>";
				
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
						joinCart_ajax(temp.product_id,'<?php echo IUrl::creatUrl("/simple/joinCart");?>','product');
						return true;
					}
				})
			}
			
		},
		error:function(){
			
		},
		complete: function(){
		
		}
	})

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