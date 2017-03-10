<?php

include_once "src/bootstrap.php";


$manager = new \Ximdex\Setup\Manager( __DIR__ ) ;
/**
 * Steps
 */

$manager->addStep( new \Ximdex\Setup\Step\Welcome( $manager ));
$manager->addStep( new \Ximdex\Setup\Step\System( $manager ));
$manager->addStep( new \Ximdex\Setup\Step\ConfigureDatabase( $manager ));
$manager->addStep( new \Ximdex\Setup\Step\InstallationProgress( $manager ));
//$manager->addStep( new \Ximdex\Setup\Step\CreateDB( $manager ));
//$manager->addStep( new \Ximdex\Setup\Step\Modules( $manager ));
$manager->addStep( new \Ximdex\Setup\Step\Settings( $manager ));




$manager->run();


