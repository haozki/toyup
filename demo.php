<?php
require ('ToyUp.class.php');

$config = array(
    //'uploadMode' => 'temp',       //上传模式：normal、temp
    'uploadPath' => './Uploaded',   //文件保存目录
    'returnData' => true,           //是否返回文件原始数据
    'returnBase64Data' => true,     //是否返回Base64格式数据
    'newName' => 'File-'.time(),            //设置新的文件名
    //'randomName' => true,           //生成随机文件名(如果同时设置了newName,则newName被覆盖)
    //'saveNameCharset' => 'GBK',   //保存文件名的默认编码(Windows平台保存中文需要GBK编码,否则是乱码)
    'maxSize' => 100,               //限制文件处理最大值(单位KB)
    'allowType' => array(           //默认接受处理的文件类型
        ".gif",
        ".png",
        ".jpg",
        ".jpeg",
        ".mp3",
        ".zip",
        ".rar",
        ".tar",
        ".tgz",
        ".txt",
        ".doc",
        ".xls",
        ".ppt",
        ".docx",
        ".xlsx",
        ".pptx"
    )
);

$ToyUp = new ToyUp('userfile',$config);
$info = $ToyUp->info();

echo '<img src="data:application/zip;base64,'.$info['base64Data'].'" alt="Data">';
echo '<a href="data:application/zip;base64,'.$info['base64Data'].'">Download</a>';
var_dump($info);

//header('Content-type: image/jpeg');
//echo base64_decode($info['base64Data']);