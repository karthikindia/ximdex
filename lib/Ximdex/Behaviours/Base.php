<?php
/**
 *  \details &copy; 2016  Open Ximdex Evolution SL [http://www.ximdex.org]
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

namespace Ximdex\Behaviours;

use Ximdex\Logger as XMD_Log;
use Ximdex\Utils\Messages;

class Base
{
    var $messages = null;
    var $options = array();
    var $required = array();
    var $optional = array();

    function __construct($options)
    {
        $this->messages = new Messages();
        $this->_checkFields($options);
        $this->options = $options;
    }

    private function _checkFields($options)
    {
        foreach ($this->required as $field) {
            if (!array_key_exists($field, $options)) {
                XMD_Log::fatal(sprintf('Field %s required in behaviour %s',
                    $field, get_class($this)));
            }
        }

        $allowedFields = array_merge($this->required, $this->optional);
        $fieldsKeys = array_keys($options);
        $extraFields = array_diff($fieldsKeys, $allowedFields);

        if (count($extraFields) > 0) {
            foreach ($extraFields as $field) {
                XMD_Log::warning(sprintf('Field %s not expected in behaviour %s',
                    $field, get_class($this)));
            }
        }
    }

    public function tearDown()
    {
        return true;
    }
}