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


use Ximdex\Runtime\App;
use Ximdex\Utils\FsUtils;
use Ximdex\Logger;


abstract class Abstract_View
{

    public function storeTmpContent($content)
    {
        //Si el contenido es una variable que contiene false ha ocurrido un error
        if ($content !== false) {
            $basePath = XIMDEX_ROOT_PATH . App::getValue('TempRoot') . "/";
            $pointer = FsUtils::getUniqueFile($basePath);
            if (isset($_GET["nodeid"])) {
                if (FsUtils::file_put_contents($basePath . "preview_" . $_GET["nodeid"] . "_" . $pointer, $content)) {
                    return $basePath . "preview_" . $_GET["nodeid"] . "_" . $pointer;
                }
            } else {
                if (FsUtils::file_put_contents($basePath . $pointer, $content)) {
                    return $basePath . $pointer;
                }
            }
        }
        Logger::error('An error has happened trying to store the content');
        return NULL;
    }

    public function retrieveContent($pointer)
    {
        return FsUtils::file_get_contents($pointer);
    }
}
