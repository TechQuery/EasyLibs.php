# EasyLibs.php

致力于做一个轻量、易用、优雅的 PHP 类库 —— 旨在不依赖任何 PHP 扩展，即可快速构建 Web 应用后端服务，支持 批量、回调、连缀风格，兼容 PHP v5.3+。


## 【主要模块（类）】

 1. 文件系统  v0.4
 2. SQLite  v0.5
 3. HTTP 服务器
   - 通用响应 —— setStatus、setHeader、setCookie、send
   - 专用响应 —— redirect、auth、download
   - REST 路由 —— 基于 `$_SERVER['PATH_INFO']`（NginX 配置方法 参考如下）
     - http://www.cnblogs.com/adu0409/articles/3359160.html
     - http://my.oschina.net/longxuu/blog/190223
 4. HTTP 客户端
   - REST 请求
   - HTTP 标准缓存 (ToDo)
 5. DOM 操作库（jQuery 兼容 API）—— 直接引用 **phpQuery**
 6. HTML 转换器  v0.3
   - 核心抽象类 —— HTMLConverter
   - 自带一个 MarkDown 规则实现类


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

## 【版本简史】

 - v2.2 Stable —— 2016年3月3日    新增 **HTMLConverter 抽象类**（HTML 转换器），且自带一个 MarkDown 规则实现类
 - v1.9 Stable —— 2015年11月15日  SQL_Table 增加 rename、addColumn 实例方法；HTTPServer 多处优化
 - v1.8 Beta   —— 2015年10月30日  **文件系统类**改继承自 SplFileInfo；HTTP 服务器 实现了 **REST 路由**
 - v1.6 Beta   —— 2015年10月29日  首个开源版本，基本模式、架构已成形