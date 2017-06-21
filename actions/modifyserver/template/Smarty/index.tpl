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
<form method="post" id='mdfsv_form' name="formulario" action='{$action_url}'>
	<input type="hidden" name="nodeid" value="{$id_node}">
	<input type="hidden" name="actionid" value="{$id_action}">
	<input type="hidden" id="nodeURL" name="nodeURL" value="{$nodeURL}">
	<input type="hidden" id="num_servers" name="num_servers" value="{$num_servers}">
	<input type="hidden" name="id" value="{if (!isset($server.id))}{$id_server}{else}none{/if}">
	<div class="action_header">
		<h5 class="direction_header"> Name Node: {$node_name}</h5>
		<h5 class="nodeid_header"> ID Node: {$nodeid}</h5>
		<hr>
	</div>
	{if !empty($messages)}
	<div class="message">
		{foreach name=messages from=$messages key=message_id item=message}
			<p>{$message.message}</p>
		{/foreach}
	</div>
	{/if}
	<div class="action_content">
		<div class="row tarjeta">
			<h2 class="h2_general">{t}Manage servers{/t}</h2>
			<fieldset class="mdfsv_errors">
				<div class="messages">
					<div class="ui-widget messages errors-container">
					</div>
				</div>
			</fieldset>
			<div id="serverid" class='server-name col1-3'>
				<div class="create-server btn main_action">{t}Create new server{/t}</div>
				{foreach from=$servers item=_server}
				<div id="server{$_server.Id}" class="row_item_selectable {if ($_server.Id eq $server.id)}selected{/if}" value="{$_server.Id}">
					{$_server.Description}
				</div>
				{/foreach}
			</div>
			<div class="server-info">
				<div class="small-12 columns">
					<div class="input">
						<input style=" margin-bottom:10px;" type="text" id='description' name='description' MAXLENGTH="100" VALUE="{$server.description}" class='server-title' placeholder="{t}New server name{/t}"/>
					</div>
					<span>
						<input type="checkbox" id='enabled_{$id_node}' name='enabled' {if ($server.enable)}checked{/if} class="input-slide"/>
						<label for='enabled_{$id_node}' class="label-slide"> {t}Enabled{/t}</label>
						<input type="checkbox" id='preview_{$id_node}' name='preview' {if ($server.preview)}checked{/if} class="input-slide">
						<label for='preview_{$id_node}' class="label-slide">{t}Preview server{/t}</label>
					</span>
				</div>
				<div style="margin-top: 100px!important;" class="content_server">
					<div name='protocol' id='protocol'>
						<label>{t}Connection{/t}</label>
		                   {foreach from=$protocols item=_protocol}
							<label for="{$_protocol.Id}">
								<input type="radio" name="protocol" id="{$_protocol.Id}" value='{$_protocol.Id}' {if ($server.protocol eq $_protocol.Id)}checked{/if} />
		                           {$_protocol.Id|gettext}</label>
		                   {/foreach}
					</div>
					<div class="remote_url">
						<label for="url" class="label_general">{t}URL or IP{/t}</label>
						<input type="text" id='url' name='url' MAXLENGTH="100" VALUE="{$server.url}" class='cajag'/>
					</div>
					<div class="remote_folder">
						<label id='labelDirectorio' for='initialdirectory' class="aligned label_general">{t}Remote directory{/t}</label>
						<input type="text" id='initialdirectory' name='initialdirectory' MAXLENGTH="100" VALUE="{$server.directory}" class='cajag'/>
					</div>
					<div class="port not_local">
						<label for='port' class="aligned label_general">{t}Port{/t}</label>
						<input type="text" id='port' name='port' MAXLENGTH="100" VALUE="{$server.port}" class='cajag'/>
					</div>
					<div class="host not_local">
						<label id='labeldirRemota' for='host' class="aligned label_general">{t}Web URL{/t}</label>
						<input style="margin-bottom:10px;" type="text" id='host' name='host' MAXLENGTH="100" VALUE="{$server.host}" class='cajag'/>
						<div class="abs_url">
							<span class="slide-element">
								<input type="checkbox" id='override_{$id_node}' name='overridelocalpaths' {if ($server.path)}checked{/if} class="input-slide"/>
								<label for='override_{$id_node}' class="label-slide">{t}Absolute URLs{/t}</label>
							</span>
						</div>
					</div>
					<div class="login not_local">
						<label for='login' class="aligned label_general">{t}User{/t}</label>
						<input type="text" id='login' name='login' MAXLENGTH="100" VALUE="{$server.user}" class='cajag'/>
					</div>
					<div class="password not_local">
						<label for='password' class="aligned label_general">{t}Password{/t}</label>
						<input type="password" id='password' name='password' class='cajag'>
					</div>
					<div class="encoding">
						<label>{t}Encoding{/t}</label>
		                   {foreach from=$encodes item=_encode}
						<label for="{$_encode.Id}">
							<input type="radio" name="encode" value='{$_encode.Id}' {if ($server.encode eq $_encode.Id)}checked{/if} id="{$_encode.Id}"/>
							{$_encode.Id}</label>
		                   {/foreach}
					</div>
					<label>{t}Channels{/t}</label>
					<div class="channels-wrapper">{if $numchannels neq 0}
						{foreach from=$channels item=_channel}
						<span class="slide-element">
							<input id='channels{$_channel.IdChannel}_{$id_node}' name='channels[]' type='checkbox' value='{$_channel.IdChannel}' {if ($_channel.InServer)}checked{/if} class="input-slide"/>
							<label for='channels{$_channel.IdChannel}_{$id_node}' class="label-slide server_channel"> {$_channel.Description|gettext}</label>
						</span>
						{/foreach}
						{else}
						<p>{t}There are no channels associated to this project{/t}.</p>
						{/if}
					</div>
					<div class="small-2 columns">
						<fieldset class="buttons-form">
							<input type="hidden" name="borrar"/>
				            {if (0 != $id_server)}
				                {button id="delete_server" label="Delete server" class="btn"}
				                {button id="update_server" label="Update" class="validate btn main_action update-server"}{*message="Would you like to create this server?"*}
				            {else}
				                {button id="save_server" label="Save" class="validate btn main_action"}{*message="Would you like to create this server?"*}
				            {/if}
						</fieldset>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>