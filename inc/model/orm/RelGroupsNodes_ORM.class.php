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
class RelGroupsNodes_ORM extends \Ximdex\Data\GenericData
{
    var $_idField = 'IdRel';
    var $_table = 'RelGroupsNodes';
    var $_metaData = array(
        'IdRel' => array('type' => "int(12)", 'not_null' => 'true', 'auto_increment' => 'true', 'primary_key' => true),
        'IdGroup' => array('type' => "int(12)", 'not_null' => 'true'),
        'IdNode' => array('type' => "int(12)", 'not_null' => 'true'),
        'IdRole' => array('type' => "int(12)", 'not_null' => 'false')
    );
    var $_uniqueConstraints = array(
        'uniq' => array('IdNode', 'IdGroup')
    );
    var $_indexes = array('IdRel');
    var $IdRel;
    var $IdGroup = 0;
    var $IdNode = 0;
    var $IdRole;
}