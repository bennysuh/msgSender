<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
Autoloader::setRootPath(__DIR__);


// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = 'YourAppBusinessWorker';
// bussinessWorker进程数量
$worker->count = 4;
// 服务注册地址
$worker->registerAddress = '127.0.0.1:1238';

// 设置处理业务的类为MyEvent
// $worker->eventHandler = 'MyEvent';

// 设置业务超时时间10秒，需要配合业务
// $worker->processTimeout = 10;
// Event.php
// 需要在文件头部增加declare(ticks=1);语句，添加后运行php start.php reload即可生效。

// 业务超时回调，可以把超时日志保存到自己想要的地方
/*$worker->processTimeoutHandler = function($trace_str, $exeption)
{
    file_put_contents('/your/path/process_timeout.log', $trace_str, FILE_APPEND);
    // 返回假，让进程重启，避免进程继续无限阻塞
    return false;
};
*/
// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

