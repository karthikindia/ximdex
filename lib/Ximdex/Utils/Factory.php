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

namespace Ximdex\Utils;

use Illuminate\Http\Request;
use Ximdex\Logger;
use Ximdex\Runtime\WebRequest;

class Factory
{

    private $_path = null;
    private $_root_name = null;
    private $_error = null;

    /**
     * @param $path string
     * @param $root_name string
     */
    public function __construct($path, $root_name)
    {
        $this->_path = $path;
        $this->_root_name = $root_name;

    }
    /**
     * Return an instance of $type Class or null if:
     *
     *  - the file is not found
     *  - the file don't contains the class $type
     *
     * @param string $type
     * @param array $args
     * @return mixed
     */
    public function instantiate($type = NULL, $args = null, WebRequest $request = null )
    {

        if (empty($request)){
            $request = WebRequest::capture();
        }

        $class = $this->_root_name;
        if (!is_null($type)) {
            $class .= $type;
        }

        // @todo -> make render load dynamic

        $nsClass = '\\Ximdex\MVC\\Render\\' . $class ;

        if ( class_exists( $nsClass )) {
            return new $nsClass( $args ) ;
        }


        $class_path = $this->_path . "/$class.class.php";

        // Add / to the beginning of class name (to prevent namespace mistake )
        if ( substr( $class, 0, 1) != '\\' ) {
            $class = '\\' . $class ;
        }
        if (!class_exists($class)) {
            if (file_exists($class_path) && is_readable($class_path)) {
                require_once($class_path);
            } else {
                $this->_setError("Factory::instantiate(): Unable to read $class_path ");
                Logger::error("Factory::instantiate(): Unable to read $class_path ");
                return NULL;
            }
        }
        if (!class_exists($class)) {
            $this->_setError("Factory::instantiate(): '$class' class not found in file $class_path");
            return NULL;
        }

        $obj = new $class($args, $request);

        if (!is_object($obj)) {
            Logger::fatal("Could'nt instanciate the class $class");
            return null ;
        }
        return $obj;
    }

    private function _setError($msg)
    {
        Logger::warning($msg);
        $this->_error = $msg;
    }

    /**
     * Returns the last error message
     *
     * @return string
     */

    public function getError()
    {
        return $this->_error;
    }

}
