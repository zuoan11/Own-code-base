<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * Redis缓存驱动 - 支持Redis集群与读写分离
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    terry <i@pengyong.info>
 */
class CacheRedis extends Cache {

    /**
     * 架构函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = array()) {
        if (!extension_loaded('redis')) {
            throw_exception(L('_NOT_SUPPERT_') . ':redis');
        }
        if (empty($options)) {
            $options = array(
                'host' => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
                'port' => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
                'timeout' => C('REDIS_TIMEOUT') ? C('REDIS_TIMEOUT') : 300,
                //pengyong 2013年12月13日 16:21:01 add config
                'persistent' => C('REDIS_PERSISTENT') ? C('REDIS_PERSISTENT') : false,
                'auth' => C('REDIS_AUTH') ? C('REDIS_AUTH') : null, //auth认证
                'rw_separate' => C('REDIS_RW_SEPARATE') ? C('REDIS_RW_SEPARATE') : false, //主从分离
            );
        }
        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $this->options['func'] = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new Redis;
    }

    /**
     * 主从连接
     * @access public
     * @param bool $master true=主连接
     */
    public function master($master = false, $num = 0) {

        $host = explode(",", $this->options['host']);

        if (count($host) > 1 && $master == false && $this->options['rw_separate'] == true) {
            array_shift($host);
        }
        if ($master == false && $this->options['rw_separate'] == true) {
            shuffle($host);
        }
//        dump($host);
        $this->options['master'] = $master == true ? $host[0] : '';
        $this->options['slave'] = $master == false ? $host[0] : '';
        $func = $this->options['func'];
        $connect = $this->options['timeout'] === false ?
                $this->handler->$func($host[0], $this->options['port']) :
                $this->handler->$func($host[0], $this->options['port'], $this->options['timeout']);
//        dump($connect);
        //pengyong 2013年12月13日 16:17:50 支持认证模式
        if ($this->options['auth'] != null) {
            $this->handler->auth($this->options['auth']);
        }
//        dump($this->options['master']);
        $this->handler->select($num); //选择链接哪个数据库默认为0
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function get($name, $num = 0) {
//        N('cache_read', 1);
        $this->master(false, $num);
        #F('read',$this->options['slave']);
        $value = $this->handler->get($this->options['prefix'] . $name);
        $jsonData = json_decode($value, true);
        return ($jsonData === NULL) ? $value : $jsonData; //检测是否为JSON数据 true 返回JSON解析数组, false返回源数据
    }
    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolen
     */
    public function set($name, $value, $num = 0, $expire = null) {
//        N('cache_write', 1);
        $this->master(true, $num);
        #F('write',$this->options['master']);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'] . $name;
        //对数组/对象数据进行缓存处理，保证数据完整性
        $value = (is_object($value) || is_array($value)) ? $this->JSON($value) : $value;
        //删除缓存操作支持
        if ($value === null) {
            return $this->handler->delete($this->options['prefix'] . $name);
        }
        if (is_int($expire) && $expire != 0) {
            $result = $this->handler->setex($name, $expire, $value);
        } else {
            $result = $this->handler->set($name, $value);
        }
        if ($result && $this->options['length'] > 0) {
            // 记录缓存队列
            $this->queue($name);
        }
        return $result;
    }
    
    /**
     * 为给定key设置生存时间。
     * @param type $name
     * @param type $expire
     * @param type $num
     * @return type
     */
    public function EXPIRE($name,$expire,$num) {
        $this->master(true, $num);
        $result = $this->handler->EXPIRE($this->options['prefix'] . $name, $expire);
        return $result;
    }
    /**
     * 查找选中库中符合给定模式的key
     * @access public
     * @param string $name keys 
     * @return array 符合给定模式的key列表
     * KEYS *命中数据库中所有key。
      KEYS h?llo命中hello， hallo and hxllo等。
      KEYS h*llo命中hllo和heeeeello等。
      KEYS h[ae]llo命中hello和hallo，但不命中hillo。
     */
    public function keys($name, $num = 0) {
        $this->master(false, $num);
        return $this->handler->keys($this->options['prefix'] . $name);
    }
    /**
     * 检查给定key是否存在
     * @access public
     * @param string $name key
     * @return boolen
     */
    public function exists($name, $num = 0) {
        $this->master(false, $num);
        return $this->handler->exists($this->options['prefix'] . $name);
    }
    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolen
     */
    public function rm($name, $num = 0) {
        $this->master(true, $num);
        return $this->handler->delete($this->options['prefix'] . $name);
    }
    /**移除给定的一个或多个key。
     */
    public function DEL($key,$num=0){
        $this->master(true, $num);
        return $this->handler->DEL($this->options['prefix'] . $key);
    }
    /**
     ** 将一个或多个值value插入到列表key的表头。
     */
    public function LPUSH($key,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->LPUSH($this->options['prefix'] . $key,$value);
    }
    /**
     ** 将一个或多个值value插入到列表key的表尾。
     */
    public function RPUSH($key,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->RPUSH($this->options['prefix'] . $key,$value);
    }
    /**
     * 移除并返回列表key的头元素。
     */
    public function LPOP($key,$num=0){
        $this->master(true, $num);
        return $this->handler->LPOP($this->options['prefix'] . $key);
    }
    /**
     * 移除并返回列表key的尾元素。
     */
    public function RPOP($key,$num=0){
        $this->master(true, $num);
        return $this->handler->RPOP($this->options['prefix'] . $key);
    }
    /**
     * 返回列表key的长度。
     */
    public function LLEN($key,$num=0){
        $this->master(false, $num);
        return $this->handler->LLEN($this->options['prefix'] . $key);
    }
    /**
     * 根据KEY返回该KEY代表的LIST的长度
     */
    public function LSIZE($key,$num=0){
        $this->master(false, $num);
        return $this->handler->LSIZE($this->options['prefix'] . $key);
    }
    /**
     * LREM key count value
     * count > 0: 从表头开始向表尾搜索，移除与value相等的元素，数量为count。
    count < 0: 从表尾开始向表头搜索，移除与value相等的元素，数量为count的绝对值。
    count = 0: 移除表中所有与value相等的值。
     * 根据参数count的值，移除列表中与参数value相等的元素。
     */
    public function LREM($key,$value,$count=0,$num=0){
        $this->master(true, $num);
        return $this->handler->LREM($this->options['prefix'] . $key,$value,$count);
    }
    /**
     * LINDEX key index 返回列表key中，下标为index的元素。
     */
    public function lIndex($key,$index,$num=0){
        $this->master(false, $num);
        return $this->handler->lIndex($this->options['prefix'] . $key,$index);
    }
    /**
     * RPOPLPUSH source destination 命令RPOPLPUSH在一个原子时间内，执行以下两个动作：
     * 将列表source中的最后一个元素(尾元素)弹出，并返回给客户端。
    将source弹出的元素插入到列表destination，作为destination列表的的头元素。
     */
    public function RPOPLPUSH($key1,$key2,$num=0){
        $this->master(true, $num);
        return $this->handler->RPOPLPUSH($this->options['prefix'] . $key1,$this->options['prefix'] . $key2);
    }
    /**
     * 将key中储存的数字值增一。
     */
    public function INCR($key,$num=0){
        $this->master(true, $num);
        return $this->handler->INCR($this->options['prefix'] . $key);
    }
    /**
     * 将key中储存的数字值增加$value。
     */
    public function INCRBY($key,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->INCRBY($this->options['prefix'] . $key,$value);
    }
    /**
     * LRANGE key start stop
    返回列表key中指定区间内的元素，区间以偏移量start和stop指定。
     */
    public function LRANGE($key,$start,$stop,$num=0){
        $this->master(false, $num);
        return $this->handler->LRANGE($this->options['prefix'] . $key,$start,$stop);
    }
    /**
     * 获取hash的所有字段
     */
    public function HKEYS($key,$num=0){
        $this->master(false, $num);
        return $this->handler->HKEYS($this->options['prefix'] . $key);
    }
    /**同时将多个field - value(域-值)对设置到哈希表key中。
     */
    public function HMSET($key,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->HMSET($this->options['prefix'] . $key,$value);
    }
    /**将单个field - value(域-值)对设置到哈希表key中。
     */
    public function HSET($key,$field,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->HSET($this->options['prefix'] . $key,$field,$value);
    }
    /**返回哈希表key中，一个或多个给定域的值。
     */
    public function HMGET($key,$fields,$num=0){
        $this->master(false, $num);
        if(is_array($fields)){
            return $this->handler->HMGET($this->options['prefix'] . $key,$fields);
        }else{
            return $this->handler->HGET($this->options['prefix'] . $key,$fields);
        }
    }
    /**获得hash的所有值
     */
    public function HVALS($key,$num=0){
        $this->master(false, $num);
        return $this->handler->HVALS($this->options['prefix'] . $key);
    }
    /**为哈希表key中的域field的值加上增量,。
     */
    public function HINCRBY($key,$field,$value,$num=0){
        $this->master(true, $num);
        return $this->handler->HINCRBY($this->options['prefix'] . $key,$field,$value);
    }
    /**删除一个或多个哈希域目前删除多个好像不行
     */
    public function HDEL($key,$fields,$num=0){
        $this->master(true, $num);
        return $this->handler->HDEL($this->options['prefix'] . $key,$fields);
    }
    /**
     * 返回哈希表key中，所有的域和值。*/
    public function HGETALL($key,$num=0){
        $this->master(false, $num);
        return $this->handler->HGETALL($this->options['prefix'] . $key);
    }

    /**
     * # 删除当前数据库所有key
     * @access public
     * @return boolen
     */
    public function clear($num = 0) {
        $this->master(true, $num);
        return $this->handler->flushDB();
    }

    /**
     * 析构释放连接
     * @access public
     */
    public function __destruct() {
        if ($this->options['persistent'] == 'pconnect') {
            $this->handler->close();
        }
    }

    /*     * ************************************************************
     *
     *    使用特定function对数组中所有元素做处理
     *    @param    string    &$array        要处理的字符串
     *    @param    string    $function    要执行的函数
     *    @return boolean    $apply_to_keys_also        是否也应用到key上
     *    @access public
     *
     * *********************************************************** */

    function arrayRecursive(&$array, $function, $apply_to_keys_also = false) {
        static $recursive_counter = 0;
        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }

            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
    }

    /*     * ************************************************************
     *
     *    将数组转换为JSON字符串（兼容中文）
     *    @param    array    $array        要转换的数组
     *    @return string        转换得到的json字符串
     *    @access public
     *
     * *********************************************************** */

    function JSON($array) {
        $this->arrayRecursive($array, 'urlencode', true);
        $json = json_encode($array);
        return urldecode($json);
    }

}