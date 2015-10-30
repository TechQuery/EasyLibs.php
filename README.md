# EasyLibs.php

致力于做一个轻量、易用、优雅的 PHP 类库 —— 旨在不依赖任何 PHP 扩展，即可快速构建 Web 应用后端服务，支持 批量、回调、连缀风格，兼容 PHP v5.3+。


## 主要模块（类）

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


## 应用实例

 1. [Web 前端跨域代理](http://git.oschina.net/Tech_Query/EasyLibs.php/blob/master/demo/XDomainProxy.php) —— http://git.oschina.net/Tech_Query/EasyWebApp/blob/master/demo/php/proxy.php


## 版本历史

 - v1.8 Beta —— 2015年10月30日 **文件系统类**改继承自 SplFileInfo；HTTP 服务器 实现了 **REST 路由**
 - v1.6 Beta —— 2015年10月29日 首个开源版本，基本模式、架构已成形