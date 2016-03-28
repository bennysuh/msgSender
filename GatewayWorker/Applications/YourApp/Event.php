<?php
/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;
use \GatewayWorker\Lib\Db;
use \GatewayWorker\Lib\Log;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Event
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    { 
        /*$logPath = __DIR__;
        $db = Db::instance('db');
        $exist = $db->select('id,status,uname')->from('tp_login')->where('uid=:uid and module=:module')->bindValues(array('uid'=>'212','module'=>'Shake'))->row();
        $L = Log::get_instance($logPath);
        $L->log(1,$exist);*/
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {

        // debug
        echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id session:".json_encode($_SESSION)." onMessage:".$message."\n";
        
        // 客户端传递的是json数据
        $message_data = json_decode($message, true);
        if(!$message_data)
        {
	   return ;
        }
        // 根据类型执行不同的业务
        switch($message_data['type'])
        {
            // 客户端回应服务端的心跳
            case 'pong':
                return;
            // 客户端登录 message格式: {type:login, name:xx, room_id:1} ，添加到客户端，广播给所有客户端xx进入聊天室
            case 'login':
                // 判断是否有房间号
                if(!isset($message_data['room_id']))
                {
                    throw new \Exception("\$message_data['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']} \$message:$message");
                }
                $existuid = Gateway::getClientIdByUid($message_data['uid']);
                if(!empty($existuid)){
                    foreach ($existuid as $k_i => $v_i) {
                        if($v_i != $client_id){
                            Gateway::unbindUid($client_id, $message_data['uid']);
                            Gateway::leaveGroup($client_id, $message_data['room_id']);
                        }
                    }
                }
                $_SESSION['room_id'] = $message_data['room_id'];
                $_SESSION['uid'] = $message_data['uid'];
                $_SESSION['client_name'] = $message_data['client_name'];
                Gateway::bindUid($client_id,$message_data['uid']);
                Gateway::joinGroup($client_id, $message_data['room_id']);
                $new_message = array('type'=>$message_data['type'], 'client_id'=>$client_id, 'client_name'=>htmlspecialchars($message_data['client_name']), 'time'=>date('Y-m-d H:i:s'));

                return Gateway::sendToGroup($message_data['room_id'], json_encode($new_message));
                
            // 客户端发言 message: {type:say, to_client_id:xx, content:xx}
            case 'say':
                // 非法请求
                /*if(!isset($_SESSION['room_id']))
                {
                    throw new \Exception("\$_SESSION['room_id'] not set. client_ip:{$_SERVER['REMOTE_ADDR']}");
                }*/
                $room_id = $_SESSION['room_id'];
                $client_name = $_SESSION['client_name'];
                // 私聊
                if($message_data['to_client_id'] != 'all')
                {
                    $new_message = array(
                        'type'=>'say',
                        'from_client_id'=>$client_id, 
                        'from_client_name' =>$message_data['from_client_name'],
                        'to_client_id'=>$message_data['to_client_id'],
                        'content'=>nl2br(htmlspecialchars($message_data['content'])),
                        'time'=>date('Y-m-d H:i:s'),
                    );
                    return Gateway::sendToUid($message_data['to_client_id'],json_encode($new_message));
                }
                
                $new_message = array(
                    'type'=>'say', 
                    'from_client_id'=>$client_id,
                    'from_client_name' =>$message_data['from_client_name'],
                    'to_client_id'=>'all',
                    'content'=>nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($message_data['room_id'] ,json_encode($new_message));
	    case 'payNumAddMes':
                $mem = new Memcache;
                $mem->connect("172.16.0.126", 11211);
                $count = $mem->get($message_data['room_id'].'count');
                $count2 = $count+1;
                $mem->set($message_data['room_id'].'count', $count2);
                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'to_client_id'=>'all',
                    'content'=>$count2,//nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($message_data['room_id'] ,json_encode($new_message));
                break;
            case 'payNumCleanMes':
                $mem = new Memcache;
                $mem->connect("172.16.0.126", 11211);
                $mem->set($message_data['room_id'].'count', 0);
                $new_message = array(
                    'type'=>'say',
                    'from_client_id'=>$client_id,
                    'to_client_id'=>'all',
                    'content'=>0,//nl2br(htmlspecialchars($message_data['content'])),
                    'time'=>date('Y-m-d H:i:s'),
                );
                return Gateway::sendToGroup($message_data['room_id'] ,json_encode($new_message));
                break;
        }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
        // debug
       echo "client:{$_SERVER['REMOTE_ADDR']}:{$_SERVER['REMOTE_PORT']} gateway:{$_SERVER['GATEWAY_ADDR']}:{$_SERVER['GATEWAY_PORT']}  client_id:$client_id onClose:''\n";
       
       // 从房间的客户端列表中删除
       if(isset($_SESSION['room_id']))
       {
           $room_id = $_SESSION['room_id'];
           $new_message = array('type'=>'logout', 'from_client_id'=>$client_id, 'from_client_name'=>$_SESSION['client_name'], 'time'=>date('Y-m-d H:i:s'));
           Gateway::sendToGroup($room_id, json_encode($new_message));
       }
   }
}
