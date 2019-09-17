<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/8
 * Time: 14:00
 */

namespace yii\di;

/**
 * Instance 表示对依赖注入（DI）容器或服务定位器的命名对象的引用
 * 可以使用 get() 来获取 $id 引用的实际对象。
 * 实例主要用于两个地方：
 * 1、配置依赖注入容器时，你使用实例引用类名、接口名或别名。 接口名或别名稍后可以通过容器将引用解析为实际对象。
 * 2、在使用服务定位器来获取依赖对象的类中。
 * 下面的示例演示了如何通过实例配置 DI 容器：
 *
 *  * ```php
 * $container = new \yii\di\Container;
 * $container->set('cache', [
 *     'class' => 'yii\caching\DbCache',
 *     'db' => Instance::of('db')
 * ]);
 * $container->set('db', [
 *     'class' => 'yii\db\Connection',
 *     'dsn' => 'sqlite:path/to/file.db',
 * ]);
 * ```
 *
 * 下面的示例显示了类如何从服务定位器检索组件：
 *
 *  * ```php
 * class DbCache extends Cache
 * {
 *     public $db = 'db';
 *
 *     public function init()
 *     {
 *         parent::init();
 *         $this->db = Instance::ensure($this->db, 'yii\db\Connection');
 *     }
 * }
 *
 * Class Instance
 * @package yii\di
 */
class Instance
{
    /**
     * @var string 组件ID,类名，接口名或者别名
     */
    public $id;

    /**
     * Instance constructor.
     * @param string $id 组件ID
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * 创建一个新的实例对象
     * @param string $id 组件ID
     * @return static yii\di\Instance 新的实例对象。
     */
    public static function of($id)
    {
        return new static($id);
    }
}