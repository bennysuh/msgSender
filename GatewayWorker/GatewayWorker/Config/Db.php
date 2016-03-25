<?php
namespace GatewayWorker\Config;
/**
 * mysql配置
 * @author walkor
 */
class Db
{
    /**
     * 数据库的一个实例配置，则使用时像下面这样使用
     * $user_array = Db::instance('user')->select('name,age')->from('users')->where('age>12')->query();
     * 等价于
     * $user_array = Db::instance('user')->query('SELECT `name`,`age` FROM `users` WHERE `age`>12');
     * @var array
     */
    //mysql的配置
    public static $db = array(
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'user'    => 'root',
        'password' => 'root',
        'dbname'  => 'weixin',
        'charset'    => 'utf8',
    );
    //memcache的配置
    public static $cachedb = array(
        'host'    => '172.16.0.126',
        'port'    => 11211,
        'expire'  => 0,
        'prefix'  => 'bongv_',
        'compression' => false,
    );
    //redis的配置
    
}