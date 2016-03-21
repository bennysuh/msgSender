1:查看Vendor下的config数据库配置.需要新建数据库并新建表
2:如果本地没有安装memcached,需要注释代码缓存的代码
3:发起登录请求网址:http:/local.dlf-m.com/index.html
4:发消息的网址:
http:/local.dlf-m.com:2121/?type=publish&to=MjEyLXN1aWtpbmctU2hha2U=&content=zcc
5:传入的参数需要进行base64加密》uid+'-'+uname+'-Shake';
6:页面需要引入socket.io.js