# EasyLibs.php

致力于做一个轻量、易用、优雅的 PHP 类库 —— 旨在不依赖任何 PHP 扩展，即可快速构建 Web 应用后端服务，支持 批量、回调、连缀风格，兼容 PHP v5.3.6+。



## 【主要模块（类）】

 1. **对象属性访问**控制器（抽象类） v0.1
 2. 文件系统  v0.4
 3. SQL 数据库类族 v0.7
 4. HTTP 服务器
   - 通用响应 —— setStatus、setHeader、setCookie、send
   - 专用响应 —— redirect、auth、download
   - REST 路由 —— 基于 `$_SERVER['PATH_INFO']`（NginX 配置方法 参考如下）
     - http://www.cnblogs.com/adu0409/articles/3359160.html
     - http://my.oschina.net/longxuu/blog/190223
 5. HTTP 客户端
   - REST 请求
   - HTTP 标准缓存 (ToDo)
 6. DOM 操作库（jQuery 兼容 API）—— 直接引用 **phpQuery**
 7. HTML 转换器  v0.3
   - 核心抽象类 —— HTMLConverter
   - 自带一个 MarkDown 规则实现类
 8. 数据模型 抽象类  v0.3



## 【应用实例】

### （一）Web 前端跨域代理

 - 核心代码 —— http://git.oschina.net/Tech_Query/EasyLibs.php/blob/master/demo/XDomainProxy.php
 - 演示项目
   - http://git.oschina.net/Tech_Query/EasyWebApp/blob/master/demo/php/proxy.php
   - http://git.oschina.net/Tech_Query/WikiWand_China/blob/master/index.php
 - 简单示例

```PHP
$_XDomain_Proxy = new XDomainProxy();

//  缓存清理
if (isset( $_GET['cache_clear'] )) {
    $_XDomain_Proxy->cache->clear();
    exit;
}
if (empty( $_GET['url'] ))  exit;

//  跨域代理
$_Time_Out = isset( $_GET['second_out'] )  ?  $_GET['second_out']  :  0;

$_XDomain_Proxy->open($_GET['url'],  is_numeric($_Time_Out) ? $_Time_Out : 0);

$_XDomain_Proxy->onError(function () {
    return array(
        'data'  =>  array(
            'code'     =>  504,
            'message'  =>  '网络拥塞，请尝试刷新本页~'
        )
    );
})->send();
```

### （二）微信 Web 开发

https://github.com/TechQuery/WeChat_Web_Demo/


### （三）PHP 文档工具

https://github.com/TechQuery/EasyDocs.php/



## 【版本简史】

 - v2.8 Stable —— 2016年11月15日  新增 **DataModel 抽象类**，方便实现 **业务数据读写**逻辑
 - v2.5 Stable —— 2016年10月26日  新增 **EasyAccess 抽象类**（对象访问控制器）
 - v2.4 Stable —— 2016年10月12日  所有 class 迁移至 **独立的类文件**，并启用 **SPL AutoLoad** 机制
 - v2.3 Stable —— 2016年4月21日   独立出 **HTTP_Request 实现类**、 **SQLDB 抽象类**，并新增 **MySQL 实现类**
 - v2.2 Stable —— 2016年3月3日    新增 **HTMLConverter 抽象类**（HTML 转换器），且自带一个 MarkDown 规则实现类
 - v1.9 Stable —— 2015年11月15日  SQL_Table 增加 rename、addColumn 实例方法；HTTPServer 多处优化
 - v1.8 Beta   —— 2015年10月30日  **文件系统类**改继承自 SplFileInfo；HTTP 服务器 实现了 **REST 路由**
 - v1.6 Beta   —— 2015年10月29日  首个开源版本，基本模式、架构已成形