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

namespace Ximdex\NodeTypes;

use description;

use Ximdex\Models\Role;



/**
 * @brief Handles roles.
 */
class RoleNode extends Root
{

	/**
	 *  Does nothing.
	 * @return null
	 */

	function RenderizeNode()
	{

		return null;
	}

	/**
	 *  Calls to method for adding a row to Roles table.
	 * @param string name
	 * @param int parentID
	 * @param int nodeTypeID
	 * @param int stateID
	 * @param string icon
	 * @param string description
	 * @return unknown
	 */

	function CreateNode($name = null, $parentID = null, $nodeTypeID = null, $stateID = null, $icon = null, $description = null)
	{

		$role = new Role();
		$role->CreateNewRole($name, $icon, $description, $this->parent->get('IdNode'));
		$this->UpdatePath();
	}

	/**
	 *  Calls to method for deleting the Role from database.
	 * @return unknown
	 */

	function DeleteNode()
	{

		//Before delete delGroupRol


		$role = new Role($this->parent->get('IdNode'));
		$role->DeleteRole();
	}

	/**
	 *  Users and Groups arent dependecies,they are associations;
	 * @return array
	 */

	function GetDependencies()
	{

		$deps = array();

		return $deps;
	}
}
