<?php
/**
 * @file makeCode.php
 * @brief 生成二维码
 * @author zyn
 * @date 2016-10-12 09:18:40
 * @version 0.1
 */

 /**
 * @class makeCode
 * @brief 
 */
class MakeCode
{
    //生成二维码
    public static function qrcode($url,$file,$size=5)
    {
        // 二维码数据 
        $url = $url; 
        // 生成的文件名 
        $filename = $file.'.png'; 
        // 纠错级别：L、M、Q、H 
        $errorCorrectionLevel = 'L';  
        // 点的大小：1到10 
        $matrixPointSize = $size;  
        //创建一个二维码文件 
        QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $logo = 'views/scsg/skin/default/images/Icon@2x1.png';//准备好的logo图片
        $QR = 'views/qrcode/'.$filename;//已经生成的原始二维码图 
        if ($logo !== FALSE) 
        {
            $QR = imagecreatefromstring(file_get_contents($QR));
            $logo = imagecreatefromstring(file_get_contents($logo));
            $QR_width = imagesx($QR);//二维码图片宽度
            $QR_height = imagesy($QR);//二维码图片高度
            $logo_width = imagesx($logo);//logo图片宽度
            $logo_height = imagesy($logo);//logo图片高度
            $logo_qr_width = $QR_width / 6;
            $scale = $logo_width/$logo_qr_width;
            $logo_qr_height = $logo_height/$scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;  //重新组合图片并调整大小
            imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,  $logo_qr_height, $logo_width, $logo_height);
            //输出图片
            imagepng($QR, 'views/qrcode/'.$filename); 
        } 
    }
}