# ToyUp
PHP文件上传类

* 支持写入文件系统和返回原始数据流两种方式
* 完善的错误信息提示
* 支持生成随机文件名
* 支持返回Base64编码格式数据

## 更新日志

ToyUp 0.0.1-alpha 2014.08.16

- 增加newName选项，用于设置新的文件名
- 随机文件名在原基础上增加md5处理
- 增加info方法作为getUploadInfo的别名
- 去除返回文件信息中的文件名和新文件名的扩展名
- 配置项returnBase64改成returnBase64Data
- 新文件名返回包含扩展名
- 增加写入目录不存在时创建目录功能
- 分离路径格式设置到独立函数
- 返回信息增加文件完整路径
- 增加文件上传模式选择选项
- 增加对输出路径的字符集设置
- 增加对上传路径的格式化


ToyUp 0.0.1-base 2013-10-11

- 创建Base版
- 增加随机文件名选项，默认为当前时间加四位随机数
- 增加文件系统内重复文件名判断(在不开启随机文件名时可用)
- 增加返回Base64编码格式选项
- 修复原始文件名返回时后缀重复
- 增加Windows平台文件名返回中文编码处理(中文乱码处理)
