<?php
/**
 * Created by PhpStorm.
 * User: jvargas
 * Date: 10/03/17
 * Time: 12:12
 */

namespace Ximdex\Setup\Step;

use Ximdex\Setup\Manager;
use Illuminate\Support\Str;
use PDO;
use PDOException;

class InstallationProgress extends Base {

    private $modules = [
        'ximIO',
        'ximSYNC',
        'ximTAGS',
        'ximTOUR',
        'ximNEWS',
        'ximPUBLISHtools',
        //   'Xowl'
    ];

    public function __construct(Manager $manager)
    {
        parent::__construct($manager);

        $this->label = 'Installation';
        $this->template = 'installationprogress.twig';
        $this->title = 'Installation progress';
        $this->vars['title'] = $this->title;

        $this->vars['form'] = $this->getForm();
    }

    public function getForm(){

    }

    public function run($step) {

        if(!isset($_GET['method'])){
            parent::run( $step );
            return;
        }

        switch ($_GET['method']){
            case 'createdb':
                try {
                    $this->checkDBConnection();
                    $this->importTables();
                    $this->sendResponse(1);
                }catch (\Exception $e){
                    $this->sendResponse(0, 'Error creating tables of database.');
                }
                break;
            case 'installmodules':
                try {
                    $modConfStr = $this->manager->render( 'files/install-modules.php.twig', [ 'modules' => $this->modules ] );
                    file_put_contents( $this->manager->getRootPath( '/conf/install-modules.php' ), $modConfStr );

                    include_once $this->manager->getRootPath( '/inc/install/managers/InstallModulesManager.class.php' );

                    foreach ( $this->modules as $module ) {
                        $this->installModule( $module );
                    }
                    $this->sendResponse(1);
                } catch (\Exception $e) {
                    $this->sendResponse(0, 'Error installing modules.');
                } catch (\Warning $e) {
                    $this->sendResponse(0, 'Error installing modules.');
                }
                break;
            default:
                $this->sendResponse(0, 'Method not found.');
        }

    }

    private function sendResponse($error, $message = ''){
        header('Content-Type: application/json');
        echo json_encode(['ok' => $error, 'message' => $message]);
    }

    /**
     * Methods to check
     */
    private function checkDBConnection()
    {
        $form = $_SESSION['db'];
        $valid = true;

        try {
            $pdconnstring = "mysql:host={$form['dbhost']};port={$form['dbport']};dbname={$form['dbname']}" ;
            $this->db = new PDO($pdconnstring, $form['dbuser'], $form['dbpass']);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


        } catch (PDOException $e) {
            $valid = false;
        }


        if ($valid === false) {
            $this->addError(
                sprintf("Unable to connect to database (error 2)"),
                sprintf("Unable to connect to database (error 2). Please check settings and try again."),
                "DB"
            );

        }
    }

    private function importTables()
    {

        if (empty($this->db)) {
            return;
        }
        $data = file_get_contents($this->manager->getFullPath("/data/sql/Ximdex_3.6_schema.sql"));
        $data .= file_get_contents($this->manager->getFullPath("/data/sql/Ximdex_3.6_data.sql"));

        try {
            $statement = $this->db->prepare($data);
            $statement->execute();
            while ($statement->nextRowset()) {/* https://bugs.php.net/bug.php?id=61613 */
            };
            $this->db->exec("UPDATE Config SET ConfigValue = '{$this->manager->getInstallRoot()}' WHERE ConfigKey = 'AppRoot'");
            $urlRoot = str_replace("index.php", "", $_SERVER['HTTP_REFERER']);
            $urlRoot = str_replace("setup/", "", $urlRoot);
            $urlRoot = strtok($urlRoot, '?');
            $urlRoot = trim($urlRoot, '/');
            $this->db->exec("UPDATE Config SET ConfigValue = '{$urlRoot}' WHERE ConfigKey = 'UrlRoot'");
            $this->db->exec("UPDATE Config SET ConfigValue = 'en_US' WHERE ConfigKey = 'locale'");

            $secret = Str::random(32);

            $this->db->exec("UPDATE Config SET ConfigValue = '{$secret}' WHERE ConfigKey = 'Secret'");

            $random = md5(rand());
            exec('openssl enc -aes-128-cbc -k "' . $random . '" -P -md sha1', $res);
            $key = explode("=", $res[1])[1];
            $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);

            $st = $this->db->prepare("UPDATE Config SET ConfigValue=:key where ConfigKey='ApiKey'");
            $st->bindParam(':key', $key);
            $st->execute();

            $st = $this->db->prepare("UPDATE Config SET ConfigValue=:iv where ConfigKey='ApiIV'");
            $st->bindParam(':iv', $iv);
            $st->execute();

            // create conf file
            $modConfStr = $this->manager->render('files/install-params.conf.php.twig', [
                    'db' => $_SESSION['db'],
                    'rootPath' => $this->manager->getRootPath("/"),
                ]
            );
            file_put_contents($this->manager->getRootPath('/conf/install-params.conf.php'), $modConfStr);

            // generate xid

            $hostName = $_SERVER["HTTP_HOST"];
            $url = "http://xid.ximdex.net/stats/getximid.php?host=$hostName";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $url,
            ));
            $resp = curl_exec($curl);
            curl_close($curl);
            if ($resp === false || empty($resp)) {
                $this->db->execute("UPDATE Config SET ConfigValue='{$resp}' where ConfigKey='ximid'");
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $this->addError(
                sprintf("Unable to create tables "),
                sprintf("Unable to create tables and data: Database must be empty & Check database permissions."),
                "DB"
            );
        }
    }

    private function installModule( $moduleName ) {
        $tempFile = $this->manager->getRootPath('/data/.' . $moduleName);
        if ( file_exists( $tempFile)) {
            unlink( $tempFile);
        }
        $mm = new \InstallModulesManager();
        $installed = $mm->installModule($moduleName);
        $isInstalled = ($installed == "Already installed" || $installed == "Installed");
        if(!$isInstalled){
            $this->addError(
                sprintf("Unable to install module %s", $moduleName),
                sprintf("Unable to install module %s", $moduleName),
                "DB"
            );
        }
        $result = $mm->enableModule($moduleName);

    }


}