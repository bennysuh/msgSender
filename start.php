<?php
use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Lib\Timer;
use PHPSocketIO\SocketIO;
use Lib\Db;
use Lib\Log;
use Lib\Basememcache;

// composer 的 autoload 文件
include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '/vendor/workerman/phpsocket.io/src/autoload.php';
include __DIR__ . '/vendor/Autoloader_Lib.php';

// PHPSocketIO服务
$sender_io = new SocketIO(2120);

// 客户端发起连接事件时，设置连接socket的各种事件回调
$sender_io->on('connection', function($socket){
    // 当客户端发来登录事件时触发
    $socket->on('login', function ($params)use($socket){
        $params_tmp = base64_decode($params);
        $param = explode('-', $params_tmp);
        $uid = $param[0];
        $uname = $param[1];
        $module = $param[2];
        $L = Log::get_instance();
        if(empty($uid) || empty($module)){
            $L->log(1,'登录日志:参数为空'.$module.'=='.$uid);
            return;
        }
        $db = Db::instance('db');
        $exist = $db->select('id,status,uname')->from('tp_login')->where('uid=:uid and module=:module')->bindValues(array('uid'=>$uid,'module'=>$module))->row();
        $cacheObj = new \Lib\Basememcache('cachedb');
        if(empty($exist)){
            $in_res = $db->insert('tp_login')->cols(array('uid'=>$uid, 'uname'=>$uname, 'status'=>1, 'createtime'=>time(),'updatetime'=>time(),'module'=>$module))->query();
            $socket->join($params);
            $socket->uid = $params;
            if($in_res >= 1){
                $cache_res = $cacheObj->set($module.$uid,array('login'=>1,'name'=>$uname));
                if(empty($cache_res)){
                    $L->log(1,'登录日志:缓存写入失败'.$cache_res);
                    return;         
                }else{
                    $L->log(1,'登录日志:登录成功'.$cache_res);
                }
            }else{
                $L->log(1,'登录日志:数据入库失败'.$in_res);
                return;
            }
        }else{
            $socket->join($params);
            $socket->uid = $params;
            if($exist['status'] == 1){
                $L->log(0,'登录日志:该用户已登录'.$uid.$module);
                return ;
            }else{
                $up_res = $db->update('tp_login')->cols(array('status'=>1,'updatetime'=>time()))->where('id='.$exist['id'])->query();
                if($up_res){
                    $has = $cacheObj->get($module.$uid);
                    if(empty($has)){
                        $cache_res_up = $cacheObj->set($module.$uid,array('login'=>1,'name'=>$exist['uname']));
                    }else{
                        $cache_res_up = $cacheObj->replace($module.$uid,array('login'=>1,'name'=>$exist['uname']));
                    }
                    if(empty($cache_res_up)){
                        $L->log(1,'更新:缓存写入失败');
                        return;         
                    }else{
                       $L->log(1,'更新:登录成功'); 
                    }
                }else{
                    $L->log(1,'更新失败');
                    return ;
                }
            }
        }
    });
    // 当客户端断开连接是触发（一般是关闭网页或者跳转刷新导致）
    $socket->on('disconnect', function () use($socket) {
        $L = Log::get_instance();
        if(!isset($socket->uid))
        {
            $L->log(1,'登出:uid获取失败'.$socket->uid);
            return;
        }
        global $sender_io;
        $db_dis = Db::instance('db');
        $exist_dis = $db_dis->select('uid,module,id,uname')->from('tp_login')->where('id= :id')->bindValues(array('id'=>$socket->uid))->row();
        if(empty($exist_dis)){
            $L->log(1,'登出:数据获取失败'.$exist_dis['id']);
            return;
        }
        $cacheObj = new \Lib\Basememcache('cachedb');
        $L->log(0,'登出:disconnect:uid:'.$socket->uid);
        $up_res = $db_dis->update('tp_login')->cols(array('status'=>0,'updatetime'=>time()))->where('id='.$socket->uid)->query();
        if($up_res){
            $hacache = $cacheObj->get($exist_dis['module'].$exist_dis['uid']);
            if($hacache['login'] == 1){
                $dis = $cacheObj->replace($exist_dis['module'].$exist_dis['uid'],array('login'=>0,'name'=>$exist_dis['uname']));
            }else{
                $dis = $cacheObj->replace($exist_dis['module'].$exist_dis['uid'],array('login'=>0,'name'=>$exist_dis['uname']));
            }
            if(empty($dis)){
                $L->log(1,'登出:缓存写入失败');
                return;         
            }else{
                $L->log(1,'登出:更新成功');
            }
        }else{
            $L->log(1,'登出:更新失败');
            return;
        }
        $L->close();
    });
});

// 当$sender_io启动后监听一个http端口，通过这个端口可以给任意uid或者所有uid推送数据
$sender_io->on('workerStart', function(){
    // 监听一个http端口
    $inner_http_worker = new Worker('http://0.0.0.0:2121');
    // 当http客户端发来数据时触发
    $inner_http_worker->onMessage = function($http_connection, $data){
        $_POST = $_POST ? $_POST : $_GET;
        $to = @$_POST['to'];
        $L = Log::get_instance();
        if(!empty($to)){
            //查询数据库是否存在
            $decode = base64_decode($to);
            $tmp_arr = explode('-',$decode);
            $db1 = Db::instance('db');
            $hasid = $db1->select('id,status')->from('tp_login')->where('uid = :u and module = :m')->bindValues(array('u'=>$tmp_arr[0],'m'=>$tmp_arr[2]))->row();
            if(empty($hasid) || $hasid['status'] != 1){
                $error = array('error'=>-1,'msg'=>'不存在');
                return $http_connection->send(json_encode($error));
            }
            //查询缓存
            /*$cacheObj = new \Lib\Basememcache('cachedb');
            $hasid = $cacheObj->get($module.$to);
            if($hasid['login'] != 1){
                $error = array('error'=>-1,'msg'=>'不存在');
                return $http_connection->send(json_encode($error));
            }*/
        }
        switch(@$_POST['type']){
            case 'publish':
                global $sender_io;
                $_POST['content'] = htmlspecialchars(@$_POST['content']);
                // 有指定uid则向uid所在socket组发送数据
                if($to){
                    $sender_io->to($to)->emit('new_msg', $_POST['content']);
                // 否则向所有uid推送数据
                }else{
                    $sender_io->emit('new_msg', @$_POST['content']);
                }
                // http接口返回ok
                $error = array('error'=>1,'msg'=>'success');
                $L->log(0,'发送消息:message:'.@$_POST['content'].':to:'.$to.':result:success');
                return $http_connection->send(json_encode($error));
        }
        $error = array('error'=>-2,'msg'=>'fail');
        $L->log(0,'发送消息:message:'.@$_POST['content'].':to:'.$to.':result:fail');
        $L->close();
        return $http_connection->send(json_encode($error));
    };
    // 执行监听
    $inner_http_worker->listen();
});

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
