<?php
/**
 * Redis缓存驱动，适合单机部署、有前端代理实现高可用的场景，性能最好
 * 有需要在业务层实现读写分离、或者使用RedisCluster的需求，请使用Redisd驱动
 */

namespace system;

class RedisPackage
{
    protected static $handler = null;
    public static $prefix = 'bogokj00001:';

    protected $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
    ];

    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {   //判断是否有扩展(如果你的apache没reids扩展就会抛出这个异常)
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func = $this->options['persistent'] ? 'pconnect' : 'connect';     //判断是否长连接
        self::$handler = new \Redis;
        self::$handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

        if ('' != $this->options['password']) {
            self::$handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            self::$handler->select($this->options['select']);
        }
    }

    /**
     * 写入缓存
     * @param string $key 键名
     * @param string $value 键值
     * @param int $exprie 过期时间 0:永不过期
     * @return bool
     */
    public static function set($key, $value, $exprie = 0)
    {
        if(is_array($value)){
            $value = json_encode($value);
        }
        if ($exprie == 0) {
            $set = self::$handler->set(self::$prefix . $key, $value);
        } else {
            $set = self::$handler->setex(self::$prefix . $key, $exprie, $value);
        }
        return $set;
    }

    /**
     * 读取缓存
     * @param string $key 键值
     * @return mixed
     */
    public static function get($key)
    {
        $fun = is_array($key) ? 'Mget' : 'get';
        return self::$handler->{$fun}(self::$prefix . $key);
    }

    /**
     * 删除缓存
     * @param string $key 键值
     * @return mixed
     */
    public static function del($fun,$key)
    {
        return self::$handler->{$fun}(self::$prefix . $key);
    }

    /**
     * 获取值长度
     * @param string $key
     * @return int
     */
    public static function lLen($key)
    {
        return self::$handler->lLen(self::$prefix . $key);
    }

    /**
     * 将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     * @return int
     */
    public static function LPush($key, $value, $value2 = null, $valueN = null)
    {
        return self::$handler->lPush(self::$prefix . $key, $value, $value2, $valueN);
    }

    /**
     * 移出并获取列表的第一个元素
     * @param string $key
     * @return string
     */
    public static function lPop($key)
    {
        return self::$handler->lPop(self::$prefix . $key);
    }


    public static function set_lock($key,$value = null,$exp=10){
        return self::$handler->set(self::$prefix . $key,$value,array('nx', 'ex' => $exp));
    }

    public static function hGet($key1,$key2){

        return self::$handler -> hGet(self::$prefix . $key1,$key2);
    }

    public static function hSet($key1,$key2,$val){

        self::$handler -> hSet(self::$prefix . $key1,$key2,$val);
    }

    public static function hDel($key1,$key2){

        self::$handler -> hDel(self::$prefix . $key1,$key2);
    }

    public static function hLen($key1){

        return self::$handler -> hLen(self::$prefix . $key1);
    }
}
