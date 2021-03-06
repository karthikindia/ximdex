<?php

namespace Ximdex\Runtime;

use DI\ContainerBuilder;
use Doctrine\Common\Cache\ArrayCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;

Class App
{
    private static $instance = null;
    private static $DBInstance = array();
    protected $DIContainer = null;
    protected $DIBuilder = null;
    protected $config = null;
    private $dispatcher = null;

    public function __construct()
    {
        $this->DIBuilder = new ContainerBuilder();
        $this->DIBuilder->useAutowiring(true);
        $this->DIBuilder->useAnnotations(true);
        $this->DIBuilder->ignorePhpDocErrors(true);
        $this->DIBuilder->setDefinitionCache(new ArrayCache());
        $this->DIContainer = $this->DIBuilder->build();
        $this->config = array();

        $this->dispatcher = new EventDispatcher();

        if (self::$instance instanceof self) {
            throw new \Exception('-10, Cannot be instantiated more than once');
        } else {
            self::$instance = $this ;
        }
    }

    public static function getInstance()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public static function config()
    {
        return self::getInstance()->getConfig();

    }

    public function getContainerObject($class)
    {
        return $this->DIContainer->get($class);

    }

    public static function getObject($class)
    {
        return self::getInstance()->getContainerObject($class);

    }

    public static function getValue($key, $default = null)
    {
        return self::getInstance()->getRuntimeValue($key, $default);

    }

    public function getRuntimeValue($key, $default = null)
    {
     if (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return $default;
        }
    }

    public static function setValue($key, $value, $persistent = false)
    {
        return self::getInstance()->setRuntimeValue($key, $value, $persistent);
    }

    public function setRuntimeValue($key, $value, $persistent = false)
    {
        if ($persistent === true) {

            $stm = self::db()->prepare('delete from Config  where ConfigKey = :key');
            $stm->execute(array(
                'key' => $key,
            ));
            $stm = self::db()->prepare('insert into Config (ConfigValue, ConfigKey ) values ( :value ,:key )');
            $stm->execute(array(
                'key' => $key,
                'value' => $value,
            ));
        }
        $this->config[$key] = $value;
        return $this;
    }
    public static function addDbConnection( \PDO $connection, $name = null ) {
        if (is_null($name)) {
            $name  = self::getInstance()->getValue('default.db', 'db');
        }
        self::$DBInstance[$name] = $connection ;
    }


    /**
     * @param string $conf
     * @return \PDO|null
     * @throws \Exception
     */
    public static function Db($conf = null)
    {
        if (is_null($conf)) {
            $conf = self::getInstance()->getValue('default.db', 'db');
        }
        if (!isset(self::$DBInstance[$conf])) {
            throw new \Exception( '-1,Unknown DB Connection');
        }
        return self::$DBInstance[$conf];
    }
    /**
     * Legacy: Compability
     */
    /**
     * @param $key
     * @return mixed|null
     */
    public static function get( $key ) {

        $value = self::getInstance()->getRuntimeValue($key,  null );
        if ( !is_null( $value )) {
            return $value ;
        }
        $objectData =  self::getInstance()->getRuntimeValue( 'class::definition::' . $key, null );
        if ( is_null( $objectData )) {

            return self::getObject( $key ) ;
        }
        require_once( App::getValue('XIMDEX_ROOT_PATH') .  $objectData  ) ;
        return self::getObject( $key ) ;
    }

    /**
     * Get the previously defined dispatcher
     *
     * @return null|EventDispatcher
     */
    public static function getDispatcher(){
        return self::getInstance()->dispatcher;
    }

    /**
     * Set a listener to an event
     *
     * @param $eventName
     * @param $listener
     */
    public static function setListener($eventName, $listener){
        self::getDispatcher()->addListener($eventName, $listener);
    }

    /**
     * Dispatch an event
     *
     * @param string $eventName
     * @param Event $event
     */
    public static function dispatchEvent($eventName, $event = null){
        self::getDispatcher()->dispatch($eventName, $event);
    }

}