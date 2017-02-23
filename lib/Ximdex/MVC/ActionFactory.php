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


//

namespace Ximdex\MVC;

use ModulesManager;
use Ximdex\Logger;
use Ximdex\Runtime\WebRequest;


/**
 *
 * @brief Factory class to instantiate Actions
 *
 * Factory class to instantiate Actions, compose a path using the request info
 * and uses the Factory class to do the dirty job
 *
 */
class ActionFactory
{


    /**
     * @param $request
     * @return mixed|null
     */
    public static function getAction(WebRequest $request)
    {
        $actionRootName = 'Action_';

        // Cogemos los datos de la accion
        $actionPath = $request->getParam( 'action_path' );
        $action = $request->getParam( 'action' );
        $module = $request->getParam( 'module' );

        $absolut_actionPath = XIMDEX_ROOT_PATH . $actionPath;

        if (!file_exists($absolut_actionPath)) {
            $actionController = NULL;
        } else {
            if (empty($module)) {
                $actionPath = XIMDEX_ROOT_PATH .
                    DIRECTORY_SEPARATOR . 'actions' .
                    DIRECTORY_SEPARATOR . $action;
            } else {
                $path_module = ModulesManager::path($module);
                $actionPath = sprintf('%s%s%s%s%s%s',
                    XIMDEX_ROOT_PATH,
                    $path_module,
                    DIRECTORY_SEPARATOR,
                    'actions',
                    DIRECTORY_SEPARATOR,
                    $action);
            }

            $factory = new \Ximdex\Utils\Factory($actionPath, $actionRootName);
            $actionController = $factory->instantiate($action, null, $request);
        }

        return $actionController;
    }

    /**
     * @param $actionPath
     * @return bool
     */
    function _actionExists($actionPath)
    {
        $absolut_actionPath = XIMDEX_ROOT_PATH . DIRECTORY_SEPARATOR . 'actions' . DIRECTORY_SEPARATOR . $actionPath;
        return file_exists($absolut_actionPath);
    }

    /**
     * @param $request
     * @return array
     */
    function _buildPath($request)
    {
        $action = $request->getParam("action");
        $actionPath = $this->request->getParam("action_path") . $action;
        $actionClass = "/Action_" . $action . ".class.php";
        //Sino es el composer visualizamos los logs para que no se nos llenen
        if ($action != "composer")
            Logger::debug("MVC::ActionFactory Executing class Action: $actionClass | path Action: $actionPath | Method Action: " . $request->getParam("method"));

        return array($actionPath, $actionClass);
    }

}
