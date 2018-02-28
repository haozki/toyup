<?php
/**
 * ToyUp Class 通用文件上传类
 * Version: 0.0.1-alpha
 * Created by Haozki.
 */
class ToyUp
{
    private $config = array(        //配置信息
        'uploadMode' => 'normal',       //上传模式：normal、temp
        'uploadPath' => '',             //文件保存目录(留空为不写入)
        'returnData' => false,          //是否返回文件原始数据
        'returnBase64Data' => false,    //是否返回Base64格式数据
        'newName' => '',                //设置新的文件名
        'randomName' => false,          //生成随机文件名(如果同时设置了newName,则newName被覆盖)
        'saveNameCharset' => 'UTF-8',   //保存文件名的默认编码(Windows平台保存中文需要GBK编码,否则是乱码)
        'maxSize' => 1000,              //限制文件处理最大值(单位KB)
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
        ),
    );
    private $fileField;             //文件域
    private $fileObject;            //文件对象
    private $fileName;              //原始文件名(包含扩展名)
    private $fileSize;              //文件大小
    private $fileType;              //文件类型(前面带'.',仅根据扩展名判断)
    private $filePath;              //文件路径
    private $saveName;              //保存时的文件名
    private $saveTarget;            //文件保存的完整路径(包括文件名和扩展名)
    private $uploadInfo = array(    //最后返回的信息
        'name' => null,                 //原始文件名(不包含扩展名)
        'size' => null,                 //文件大小
        'type' => null,                 //文件类型(前面带'.',仅根据扩展名判断)
        'state' => null,                //文件上传状态
        'newName' => null,              //新的文件名(不包含扩展名)
        'path' => null,                 //文件位置物理绝对路径(包括文件名和扩展名)
        'oriData' => null,              //文件二进制数据
        'base64Data' => null            //文件Base64数据
    );
    private $stateInfo;             //上传状态信息,
    private $stateMap = array(      //上传状态映射表
        UPLOAD_ERR_OK => 'UPLOAD_ERR_OK',
        //其值为 0，没有错误发生，文件上传成功。

        UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
        //其值为 1，上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值。

        UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
        //其值为 2，上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。

        UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
        //其值为 3，文件只有部分被上传。

        UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
        //其值为 4，没有文件被上传。

        UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
        //其值为 6，找不到临时文件夹。PHP 4.3.10 和 PHP 5.0.3 引进。

        UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
        //其值为 7，文件写入失败。PHP 5.1.0 引进。

        UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION',
        //其值为 8，File upload stopped by extension。最新增加的。

        'FILE_NOT_EXIST' => 'FILE_NOT_EXIST',
        //上传的文件不存在

        'FILE_SIZE_EXCEED' => 'FILE_SIZE_EXCEED',
        //上传的文件超出后台接受处理的最大值

        'FILE_TYPE_LIMIT' => 'FILE_TYPE_LIMIT',
        //文件类型不在接受处理的范围之内

        'FILE_NAME_EXIST' => 'FILE_NAME_EXIST',
        //文件系统中已经存在同名文件

        'FOLDER_MAKE_FAILED' => 'FOLDER_MAKE_FAILED',
        //创建目标目录失败

        'FILE_MOVE_ERROR' => 'FILE_MOVE_ERROR',
        //移动原始文件时出错

        'UNKNOWN_ERROR' => 'UNKNOWN_ERROR' ,
        //未知错误
    );

    /**
     * 构造函数
     * @param string $fileField 表单名称
     * @param array $config  配置项
     */
    public function __construct($fileField, $config)
    {
        $this->fileField = $fileField;
        $this->fileObject = $_FILES[$this->fileField];
        $this->stateInfo = $this->stateMap[UPLOAD_ERR_OK];
        $this->initialize($config);
        $this->upFile();
    }

    /**
     * 初始化配置项
     * @param array $config  配置项
     * @return mixed
     */
    private function initialize($config = array())
    {
        foreach ($config as $key => $val)
        {
            if (isset($this->config[$key]))
            {
                $this->config[$key] = $val;
            }
        }
        // 格式化上传路径为合法值
        $this->config['uploadPath'] = $this->setUploadPath($this->config['uploadPath']);
    }

    /**
     * 上传文件的主处理方法
     * @return mixed
     */
    private function upFile()
    {
        $file = $this->fileObject;

        // 通过表单文件对象数组的错误值判断错误类型
        if ($file['error']){
            $this->stateInfo = $this->getStateInfo($file['error']);
            return;
        }

        // 检查上传后文件是否存在
        if (!is_uploaded_file($file['tmp_name'])){
            $this->stateInfo = $this->getStateInfo('FILE_NOT_EXIST');
            return;
        }

        // 初始化文件基本信息
        $this->fileName = $this->getFileName();
        $this->fileSize = $file['size'];
        $this->fileType = $this->getFileExt();
        $this->filePath = $file['tmp_name'];

        if ($this->config['uploadMode'] == 'temp') return;

        // 检查文件大小是否超出后台接受处理的最大值
        if (!$this->checkSize()){
            $this->stateInfo = $this->getStateInfo('FILE_SIZE_EXCEED');
            return;
        }

        // 检查文件类型是否在接受上传的范围之内
        if (!$this->checkType()){
            $this->stateInfo = $this->getStateInfo('FILE_TYPE_LIMIT');
            return;
        }

        if ($this->stateInfo == $this->stateMap[UPLOAD_ERR_OK]) {
            // 保存文件数据到返回数组(如果下面使用move_uploaded_file移动文件则这一步操作必须先进行)
            if ($this->config['returnData']){
                $this->uploadInfo['oriData'] = fread(fopen($file['tmp_name'], "r"), $this->fileSize);
            }
            if ($this->config['returnBase64Data']){
                $this->uploadInfo['base64Data'] = base64_encode(fread(fopen($file['tmp_name'], "r"), $this->fileSize));
            }

            // 写入文件数据到目录
            if ($this->config['uploadPath'] != ''){
                // 生成保存时的文件名
                $this->saveName = $this->makeSaveName();

                // 生成最终的目标位置
                $this->saveTarget = $this->makeTarget();

                // 处理目录文件夹
                if (!file_exists($this->config['uploadPath'])){
                    if (!$this->makeFolder()){
                        $this->stateInfo = $this->getStateInfo('FOLDER_MAKE_FAILED');
                        return;
                    }
                }

                // 如果没有开启随机文件名，且开启了写入数据到文件系统则判断是否有重名文件
                if ($this->checkExist()){
                    $this->stateInfo = $this->getStateInfo('FILE_NAME_EXIST');
                    return;
                }

                // 移动源文件
                if (!move_uploaded_file($file['tmp_name'], $this->saveTarget)){
                    $this->stateInfo = $this->getStateInfo('FILE_MOVE_ERROR');
                    return;
                }else{
                    $this->filePath = realpath($this->saveTarget);
                }
            }
        }
    }

    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    private function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap['UNKNOWN_ERROR'] : $this->stateMap[$errCode];
    }

    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getUploadInfo()
    {
        $this->uploadInfo['name'] = $this->fileName;
        $this->uploadInfo['size'] = $this->fileSize;
        $this->uploadInfo['type'] = $this->fileType;
        $this->uploadInfo['state'] = $this->stateInfo;
        $this->uploadInfo['path'] = $this->filePath;
        return array_filter($this->uploadInfo, function($val){
            return (!$val==null);
        });
    }

    /**
     * 成员方法getUploadInfo的别名
     * @return array
     */
    public function info()
    {
        return $this->getUploadInfo();
    }

    /**
     * 获取文件名(不带扩展名)
     * @return string
     */
    private function getFileName()
    {
        return basename($this->fileObject['name'], $this->getFileExt());
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    private function getFileExt()
    {
        return strtolower(strrchr($this->fileObject['name'], '.'));
    }

    /**
     * 文件大小检测
     * @return bool
     */
    private function checkSize()
    {
        return $this->fileSize <= ($this->config['maxSize'] * 1024);
    }

    /**
     * 文件类型检测
     * @return bool
     */
    private function checkType()
    {
        return in_array($this->fileType, $this->config['allowType']);
    }

    /**
     * 重命名文件
     * @return string
     */
    private function randName()
    {
        return md5(time() . rand(1000, 9999));
    }

    /**
     * 生成保存时的文件名
     * @return string
     */
    private function makeSaveName()
    {
        $saveName = $this->fileName;
        // 如果 $config['randomName'] = true 则生成随机文件名
        if ($this->config['newName']){
            $saveName = $this->config['newName'];
            $this->uploadInfo['newName'] = $saveName;
        }
        if ($this->config['randomName']){
            $saveName = $this->randName();
            $this->uploadInfo['newName'] = $saveName;
        }
        return $this->transcoding($saveName);
    }

    /**
     * 生成目标文件保存位置
     * @return string
     */
    private function makeTarget()
    {
        return $this->config['uploadPath'] . $this->saveName . $this->fileType;
    }

    /**
     * 生成目标文件夹
     * @return bool
     */
    private function makeFolder()
    {
        return mkdir($this->config['uploadPath'], 0777, true);
    }

    /**
     * 检查文件是否已经存在
     * @return bool
     */
    private function checkExist()
    {
        return file_exists($this->saveTarget);
    }

    /**
     * 设置路径格式
     * @return string
     */
    private function setUploadPath($path)
    {
        if ($path != ''){
            $path = rtrim($path, '/') . '/';
        }
        return $this->transcoding($path);
    }
    /**
     * 编码转换
     * @return string
     */
    private function transcoding($string)
    {
        /*
        如果以原始文件名输出，且文件系统是Windows平台
        由于PHP默认使用UTF-8编码，而中文Windows平台使用GB2312编码
        那么写入文件包含中文则会显示乱码(如move_uploaded_file函数)
        可以使用iconv("原编码","转化编码","字符串")或者mb_convert_encoding("字符串","转化编码",原编码")来转换编码;
        如果不确定默认编码是UTF-8则可以使用mb_detect_encoding("字符串")函数检测原字符串编码
        */
        // 处理文件名字符编码
        if ($this->config['saveNameCharset'] != 'UTF-8'){
            $string = iconv("UTF-8",$this->config['saveNameCharset'],$string);
            //$string = mb_convert_encoding($string,"GB2312","UTF-8");
        }
        return $string;
    }
}