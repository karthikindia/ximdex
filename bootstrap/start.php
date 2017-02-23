<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Ximdex\Runtime\App ;

/* Debug only */
// putenv ( 'APP_DEBUG=true' );

// for legacy compatibility
if (!defined('XIMDEX_ROOT_PATH')) {
    define('XIMDEX_ROOT_PATH', dirname(dirname(__FILE__)));
}


if (!defined('CLI_MODE'))
    define('CLI_MODE', 0);



include_once dirname(dirname(__FILE__)) . '/extensions/vendors/autoload.php';


class_alias('Ximdex\Modules\Manager', 'ModulesManager');


require_once dirname(dirname(__FILE__)) . '/extensions/vendors/illuminate/support/helpers.php';


// Initialize App
class_alias('Ximdex\Runtime\App', 'App');
App::setValue('XIMDEX_ROOT_PATH', dirname(dirname(__FILE__)));

include_once(XIMDEX_ROOT_PATH . '/inc/db/DB_zero.class.php');



// get Config from install file
if ( file_exists( App::getValue('XIMDEX_ROOT_PATH') . '/conf/install-params.conf.php' ) ) {
    $conf = require_once(App::getValue('XIMDEX_ROOT_PATH') . '/conf/install-params.conf.php');
                 foreach ($conf as $key => $value) {
        App::setValue($key, $value);
    }
}

// Initialize Modules Manager
// set ximdex root path
Ximdex\Modules\Manager::init( App::getValue('XIMDEX_ROOT_PATH')   );
Ximdex\Modules\Manager::file( Ximdex\Modules\Manager::get_modules_install_params() );

// setup log
include_once XIMDEX_ROOT_PATH . '/conf/log.php';

// read install-modules.php
$modulesConfString = "";
$installModulesPath = App::getValue('XIMDEX_ROOT_PATH') . '/conf/install-modules.php';
if( file_exists($installModulesPath) ){
    $modulesConfString = file_get_contents($installModulesPath);
}

$matches = array();
preg_match_all('/define\(\'(.*)\',(.*)\);/iUs', $modulesConfString, $matches);
foreach ($matches[1] as $key => $value) {
    App::setValue($value, str_replace('\'', '', $matches[2][$key]));
}

// use config values
define('DEFAULT_LOCALE', App::getValue('locale', 'ES_es'));
date_default_timezone_set(App::getValue('timezone', 'Europe/Madrid'));

// set DB Connection
$dbConfig = App::getValue('db');
if ( !empty( $dbConfig ) ) {
    $dbConn = new \PDO("{$dbConfig['type']}:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']}",
        $dbConfig['user'], $dbConfig['password']);
    $dbConn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    App::addDbConnection($dbConn);

// get Persistent Config
    $stm = App::Db()->prepare('select * from Config');
    $stm->execute();
    foreach ($stm as $row) {
        App::setValue($row['ConfigKey'], $row['ConfigValue']);
    }
}

// special objects (pseudo-DI)
// App::setValue( 'class::definition::Messages',       '/inc/helper/Messages.class.php' );
class_alias('Ximdex\Utils\Messages', 'Messages');
App::setValue( 'class::definition::DB',             '/inc/db/DB.class.php' );
// App::setValue( 'class::definition::XMD_log',        '/inc/log/XMD_log.class.php' );

// Extensions setup

$mManager = new ModulesManager;

/**
 * Execute function init for each enabled module
 */
foreach(ModulesManager::getEnabledModules() as $module){
    $name = $module["name"];
    $moduleInstance = $mManager->instanceModule($name);
    if( method_exists( $moduleInstance, 'init' ) ){
        $moduleInstance->init();
    }
}

 // FROM MVC

if (!defined('RENDERER_ROOT_PATH')) {
    define('RENDERER_ROOT_PATH', XIMDEX_ROOT_PATH . '/inc/mvc/renderers');
}
if (!defined('SMARTY_TMP_PATH')) {
    define('SMARTY_TMP_PATH', XIMDEX_ROOT_PATH . App::getValue('TempRoot'));
}

