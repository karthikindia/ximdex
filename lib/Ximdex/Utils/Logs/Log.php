<?php
/**
 *  \details &copy; 2011  Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 * @author Ximdex DevTeam <dev@ximdex.com>
 * @version $Revision$
 */


namespace Ximdex\Utils\Logs;

use Ximdex\Utils\AssociativeArray;
use Ximdex\Utils\Session;

if (!defined('XIMDEX_ROOT_PATH'))
    define('XIMDEX_ROOT_PATH', realpath(dirname(__FILE__) . "/../../"));

if (!defined('XIMDEX_LOG_PATH'))
    define('XIMDEX_LOG_PATH', XIMDEX_ROOT_PATH . "/inc/log/");

/*------------------------------------------------------------------------*/


/**
 * Class Log
 * @package Ximdex\Utils\Logs
 */
class Log
{

    /**
     * @var \Ximdex\Utils\AssociativeArray
     */
    var $_loggers;

    /**
     * @var
     */
    var $_getters;

    /**
     * @var
     */
    var $_config;

    /**
     * Log constructor.
     * @param bool $key
     * @param string $name
     */
    public function __construct($key = false, $name = 'default')
    {

        // to ensure singleton.
        if ($key != M_PI) {
            die('Use $obj =& ::getInstance(); for Log construction!');
        }

        // normal constructor

        // init data structures
        $this->_loggers = new  AssociativeArray();
    }


    /**
     *
     */
    function _abstract()
    {
        die("Log: Must override abstract function in child class.");
    }


    /**
     * @param string $name
     * @return null|Log
     */
    public static function & getInstance($name = 'default')
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new Log(M_PI, $name);
        }

        return $instance;
    }

    /**
     * TODO: Unify all factories in genericFactory.
     */
    /**
     * @param $type
     * @param $template
     * @return mixed
     */
    function & factoryLayout($type, $template)
    {

        $class = "Layout_$type";
        //  $class_path = XIMDEX_LOG_PATH . "/layout/$class.class.php";

        //  if (!class_exists($class)) require_once($class_path);

        $obj = new $class($template);

        return $obj;
    }


    /**
     * @param $params
     * @return mixed
     */
    function & factoryAppender($params)
    {

        $class = "Appender_{$params['type']}";
        //   $class_path = XIMDEX_LOG_PATH . "/appender/$class.class.php";

        //  if (!class_exists($class)) // require_once($class_path);

        $obj = new $class($params);

        return $obj;
    }


    /**
     * @param $type
     * @param $layout
     * @param $params
     * @return mixed
     */
    function & factoryGetter($type, &$layout, $params)
    {

        $class = "Getter_$type";
        // $class_path = XIMDEX_LOG_PATH . "/getter/$class.class.php";

        // if (!class_exists($class)) require_once($class_path);

        $obj = new $class($layout, $params);

        return $obj;
    }


    /**
     * @param $name
     * @param $type
     * @param $params
     * @return mixed
     */
    function & factoryLogger($name, $type, $params)
    {

        $class = "Logger_$type";
        // $class_path = XIMDEX_LOG_PATH . "/logger/$class.class.php";

        // if (!class_exists($class)) require_once($class_path);

        $obj = new $class($name, $params);

        return $obj;
    }

    /**
     * @return array
     */
    function & getLoggers()
    {

        return $this->_loggers->getArray();
    }

    /**
     * @param $name
     * @param $logger
     */
    public static function attachLogger($name, &$logger)
    {

        $log =& self::getInstance();

        $log->_loggers->set($name, $logger);
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function existLogger($name)
    {
        $log =& self::getInstance();

        return $log->_loggers->exist($name);
    }


    /**
     * @param string $name
     * @return Log
     */
    public static function getLogger($name = 'default')
    {

        $log =& self::getInstance($name);

        if (!$log->existLogger($name)) {
            if (!$log->setUp($name)) {
                return NULL;
            }
        }

        $loggers =& $log->getLoggers();

        if (isset($loggers[$name])) {
            return $loggers[$name];
        }

        trigger_error("Logger '$name' not found in loggers.", E_USER_ERROR);
        return NULL;
    }


    /**
     * @param $loggerName
     * @return bool
     */
    function setUp($loggerName)
    {

        $configPath = XIMDEX_ROOT_PATH . '/conf/log.php';

        include($configPath);

        if (isset($config[$loggerName])) {
            $params = &$config[$loggerName];
            $loggerParams = &$params['logger_params'];
            $layoutParams = &$params['layout_params'];
            $appenderParams = &$params['appender_params'];
            $getterParams = &$params['getter_params'];
        } else {
            $msg = sprintf("%s: Error al crear el logger %s, no esta configurado en log-config.ini en %s, %s", __FUNCTION__, (empty($loggerName) ? "'empty'" : $loggerName), __FILE__, __LINE__);
            error_log($msg);
            return false;
        }

        if (isset($layoutParams['type'])) {
            $layout_type = $layoutParams['type'];
            $layout_tpl = $layoutParams['template'];
            $layout_obj =& self::factoryLayout($layout_type, $layout_tpl);
        }

        if (empty($layout_obj)) {
            $msg = sprintf("%s: Error al crear el layout '%s' para el logger '%s' en %s, %s", __FUNCTION__, (empty($layoutParams['type']) ? 'empty' : $layoutParams['type']), $loggerName, __FILE__, __LINE__);
            error_log($msg);
            return false;
        }

//		$params['logger_params'] es opcional
        if (!empty($appenderParams['file'])) {
            // Necesario tanto para getters como para appenders
            //$appender_params = $appenderParams['file'];
            $logger_obj =& self::factoryLogger($loggerName, "error", $loggerParams);
        }

        if (empty($logger_obj)) {
            $msg = sprintf("%s: Error al crear el logger %s, no se pudo obtener el parametro 'file' en %s, %s", __FUNCTION__, (empty($loggerName) ? "'empty'" : $loggerName), __FILE__, __LINE__);
            error_log($msg);
            return false;
        }

        if (isset($appenderParams['type']) && !empty($layout_obj)) {
            $appender_type = $appenderParams['type'];
            // Se debe establecer el layout en este punto, en la configuracion no es posible
            $appenderParams['layout'] = &$layout_obj;
            $appender_obj =& self::factoryAppender($appenderParams /*$appender_type, $layout_obj, $appender_params*/);
            $logger_obj->attachAppender($appender_type, $appender_obj);
        }

        if (isset($appenderParams['type'])) {
            $getter_type = $appenderParams['type'];

            $getter_params = array();
            reset($params['getter_params']);
            while (list($key, $value) = each($params['getter_params'])) {
                $getter_params[$key] = $value;
            }
            $getter_params['file'] = isset($params['file']) ? $params['file'] : $appenderParams['file'];

            $getter_obj = &self::factoryGetter($getter_type, $layout_obj, $getterParams);
            $logger_obj->attachGetter($getter_type, $getter_obj);
        }

        self::attachLogger($loggerName, $logger_obj);
        return true;

    }


    /**
     * @param $logger
     * @param $message
     * @return bool
     */
    function debug($logger, $message)
    {

        if (!self::existLogger($logger)) {
            if (!self::setUp($logger)) {
                error_log("No se ha podido crear un logger de tipo $logger en openDocumentConverter.class.php");
                return false;
            }
        }

        $logger =& self::getLogger($logger);
        $logger->debug($message . '- Usuario: ' . Session::get('userID'));
    }
}
