{**
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
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 *}

<form method="post" id="cdx_form" action="{$action_url}">
	<input type="hidden" name="nodeid" value="{$idNode}"/>

	<div class="action_header">
		<h5 class="direction_header"> Name Node: {$node_name}</h5>
		<h5 class="nodeid_header"> ID Node: {$nodeid}</h5>
		<hr>
	</div>
	<div class="action_content icon">
		<div class="row tarjeta">
			<div class="small-12 columns title_tarjeta">
				<h2 class="h2_general">{t}Add new XML{/t}</h2>
			</div>
			<div class="small-8 columns">
				<div class="input">
			<label for="docname" class="label_title label_general">{t}Name{/t} *</label>
			<p  class="icon_especial input-select icon document">
				<input type="text" name="name" id="docname" class="input_general validable not_empty full-size" placeholder="{t}Obligatory field{/t}"/>
			</p></div></div>
				<div class="small-4 columns">
					<div class="input-select icon">
						<label for="id_schema" class="label_title label_general">{t}Schema{/t}</label>
				<select name="id_schema" id="schemaid" class="cajaxg validable not_empty document-type">
					<option value="">{t}Select schema{/t}</option>
                    {foreach from=$schemes item=schema}
						<option value="{$schema.idSchema}">{$schema.Name}</option>
                    {/foreach}
				</select>
					</div></div>
            {if $schemes|@count==0}
			<div class="small-12 columns">
				<div class="alert alert-info">
					<strong>Info!</strong> {t}No schemes found{/t}.<br>{t}Maybe you need to set properly the type of your RNG schemes (performing the <em>Modify properties</em> action on them) or create/upload a new one{/t}.
				</div>
			</div>
				{/if}

            {include file="`$_APP_ROOT`/actions/createxmlcontainer/template/Smarty/_ximdoc_languages.tpl"}

			<div class="small_12 columns">
	<fieldset class="buttons-form ">
        {button label="Create" class='validate btn main_action' }{*message="Do you wan to create the XML document?"*}
	</fieldset>
			</div></div></div>
</form>
