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
<form method="post" name="workflow_forward" action="{$action_url}">
    <input type="hidden" name="nodeid" value="{$id_node}" />
    <input type="hidden" name="default_message" value="{$defaultMessage}">
    <input type="hidden" name="groupid" value="" />
    <input type="hidden" name="state" value="{$state}" />
    <input type="hidden" name="stateid" value="{$stateid}" />

    <div class="action_header">
        <h5 class="direction_header"> Name Node: {$name}</h5>
        <h5 class="nodeid_header"> ID Node: {$nodeid}</h5>
        <hr>

    </div>

    {if isset($hasDisabledFunctions) and $hasDisabledFunctions}
        <div class="message message-warning">
            <p class="disable-functions-alert">
                {t}<i>pcntl_fork</i> and <i>pcntl_waitpid</i> required functions are both disabled{/t}.{t}This could cause a slow and unstable publishing. Please, check the <i>php.ini</i> configuration file or contact with your system administrator{/t}.
            </p>
        </div>
    {/if}

    {if isset($globalForcedEnabled) and $globalForcedEnabled}
        <div class="message message-warning">
            <p class="disable-functions-alert">
                {t}Force publication is enabled in global config. This could cause slow publication. Please check synchro.conf file{/t}.
            </p>
        </div>
    {/if}

    <div class="action_content">
        <fieldset class="publish_date">
            <div class="xim_calendar_container">
                <calendar
                        timestamp="{$timestamp_from}"
                        date_field_name="date"
                        hour_field_name="hour"
                        min_field_name="min"
                        sec_field_name="sec"
                        format="d-m-Y H:i:s"
                        cname="dateUp"
                        type="interval"
                        first_date_label="{t}from{/t}"
                        last_date_label="{t}to{/t}"
                        first_date_function=""
                        last_date_function=""
                        first_date_name="dateUp"
                        last_date_name="dateDown"
                />

                <p class="next_state">
                    {t}Next state{/t}: <strong>{$state}</strong>
                </p>

                <fieldset class="notifications">
					<span class="">
		    			<input type="checkbox" name="sendNotifications" id="sendNotifications_forward_{$idNode}" class="send-notifications hidden-focus" value="1" {if $required == 1}checked="checked"{/if} />
						<label for="sendNotifications_forward_{$idNode}" class="checkbox-label icon">{t}Send notifications{/t}</label>
					</span>
                    <ol>
                        <li class="conditioned {if $required != 1}hidden{/if}">
                            <label for="groups" class="label_title label_general">{t}Group{/t}</label>
                            <div class="input-select">
                            <select id="groups" name="groups" class="cajaxg group_info">
                                {counter assign=index start=1}
                                {foreach from=$group_state_info key=index item=group}
                                    <option value="{$group.IdGroup}|{$group.IdState}" {if $index == 0}selected="selected"{/if}>{$group.groupName} ({$group.stateName})</option>
                                    {counter assign=index}
                                {/foreach}
                            </select></div>
                        </li>

                        <li class="conditioned {if $required != 1}hidden{/if}">
                            <label class="label_title label_general">{t}Users{/t}</label>
                            <div class="user-list-container">
                                <ol class="user-list">
                                    {if (isset($notificableUsers))}
                                        {counter assign=index start=1}
                                        {foreach from=$notificableUsers item=notificable_user_info}
                                            <li class="user-info">
                                                <input type="checkbox" name="users[]" class="validable notificable check_group__notificable hidden-focus" id="user_{$notificable_user_info.idUser}" value="{$notificable_user_info.idUser}" {if $index == 1}checked="checked"{/if} />
                                                <label for="user_{$notificable_user_info.idUser}" class="checkbox-label icon">{$notificable_user_info.userName}</label>
                                            </li>
                                            {counter assign=index}
                                        {/foreach}
                                    {/if}
                                </ol>
                            </div>
                        </li>

                        <li class="conditioned {if $required != 1}hidden{/if}">
                            <label for="texttosend" class="label_title label_general">{t}Comments{/t}:</label>
                            <textarea style="border: 2px solid lightgray!important;" class="validable not_empty comments" name="texttosend" id="texttosend" rows="4" wrap="soft" tabindex="7">{$defaultMessage}</textarea>
                        </li>
                    </ol>
                </fieldset>
            </div>

            <div class="programed_publication row-item">
                <h2 class="icon clock" style="color:white!important; margin-bottom:0!important;">{t}Scheduled publications{/t}</h2>
                {if $gap_info|@count gt 0}
                    {foreach from=$gap_info key=index item=gap}
                        <div class="publication">
					    <span class="start_publication">{t}From{/t}
                            <span class="publication_date">{$gap_info[$index]['BEGIN_DATE']}</span>
						    <span class="publication_time"></span>
					    </span>
                            <span class="end_publication">{t}To{/t}
                                <span class="publication_date">{$gap_info[$index]['END_DATE']}</span>
						    <span class="publication_time"></span>
					    </span>
                        </div>
                    {/foreach}
                {else}
                    <div class="not_programed">{t}You don't have scheduled publications yet{/t}.</div>
                {/if}
            </div>
            {if $has_unlimited_life_time}
                <p>{t}Warning: The last publication window has not defined any end-date{/t}.</p>
                <input type="checkbox" name="markend" id="markend" />
                <label for="markend">{t}Do you want to set the begin-date of the new window as the end-date of the previous window?{/t}</label>
            {/if}

        </fieldset>
        <div class="small-12 columns">
        <fieldset class="buttons-form">
            {button class="validate publicate-button  btn main_action" label="Publish"}
        </fieldset>
        </div></div>
</form>