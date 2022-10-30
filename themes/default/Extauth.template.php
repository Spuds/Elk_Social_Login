<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2022 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.1.0
 *
 * This addon is based on code from:
 * @author Antony Derham
 * @copyright 2014 Antony Derham
 *
 */

/**
 * Show the list of connected accounts and those that can be connected
 */
function template_action_profile()
{
	global $context, $scripturl, $txt;

	echo '
	<h2 class="category_header">', $txt['connected_accounts'], '</h2>
	<p class="description">', $txt['connected_accounts_desc'], '</p>
	<div class="roundframe">
		<dl class="settings">';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
			<dt class="righttext">', $provider, '</dt>';

		// Remove any connected account
		if (in_array($provider, $context['connected_providers'], true))
		{
			echo '
			<dd>
				<a class="linkbutton alert" href="', $scripturl, '?action=extauth;provider=', $provider, ';sa=deauth;member=', $context['member']['id'], ';', $context['session_var'], '=', $context['session_id'], '">
					<div class="extauth_', strtolower($provider), '">
						<span style="margin-left:2.5em"> ', $txt['disconnect'], ' ', $provider, '</span>
					</div>
				</a>
			</dd>';
		}
		// Make a new connection
		else
		{
			echo '
			<dd>
				<a class="linkbutton" href="', $scripturl, '?action=extauth;provider=', $provider, ';sa=auth;member=', $context['member']['id'], ';', $context['session_var'], '=', $context['session_id'], '">
					<div class="extauth_', strtolower($provider), '">
						<span style="margin-left:2.5em"> ', $txt['connect'], ' ', $provider, '</span>
					</div>
				</a>
			</dd>';
		}
	}

	echo '
		</dl>
	</div>';
}

/**
 * This is the registration page for external authentication.  It is mostly the same as the
 * standard registration (checkbox agreement) one but the form submit is for the ExtAuth addon
 */
function template_registration()
{
	global $context, $scripturl, $txt;

	echo '
	<form action="', $scripturl, '?action=extauth;sa=register2" name="registration" id="registration" method="post" accept-charset="UTF-8">
		<h2 class="category_header">', $txt['extauth_register'], '</h2>
		<p class="infobox">', $txt['extauth_reg_notice'], '</p>';

	// Any errors?
	if (!empty($context['registration_errors']))
	{
		echo '
		<div class="errorbox">
			<span>', $txt['registration_errors_occurred'], '</span>
			<ul>';

		// Cycle through each error and display an error message.
		foreach ($context['registration_errors'] as $error)
		{
			echo '
				<li>', $error, '</li>';
		}

		echo '
			</ul>
		</div>';
	}

	echo '
		<div class="content">
			<fieldset class="content">
				<dl class="settings">
					<dt>
						<label for="username">', $txt['username'], ':</label>
					</dt>
					<dd>
						<input type="text" name="user" id="username" size="30" tabindex="', $context['tabindex']++, '" maxlength="25" value="', $context['username'] ?? '', '" class="input_text" placeholder="', $txt['username'], '" required="required" autofocus="autofocus" />
					</dd>';

	if ($context['insert_display_name'])
	{
		echo '
					<dt>
						<label for="displayname">', $txt['display_name'], ':</label>
					</dt>
					<dd>
						<input type="text" name="display" id="displayname" size="30" tabindex="', $context['tabindex']++, '" maxlength="25" value="', $context['display_name'] ?? '', '" class="input_text" placeholder="', $txt['display_name'], '" required="required" />
					</dd>';
	}

	echo '
					<dt>
						<label for="reserve1">', $txt['user_email_address'], ':</label>
					</dt>
					<dd>
						<input type="email" name="email" id="reserve1" size="30" tabindex="', $context['tabindex']++, '" value="', $context['email'] ?? '', '" class="input_text" placeholder="', $txt['user_email_address'], '" required="required" />
					</dd>
 					<dt>
 						<label for="notify_announcements">', $txt['notify_announcements'], ':</label>
 					</dt>
 					<dd>
 						<input type="checkbox" name="notify_announcements" id="notify_announcements" tabindex="', $context['tabindex']++, '"', $context['notify_announcements'] ? ' checked="checked"' : '', ' class="input_check" />
 					</dd>
				</dl>';

	// If there is any field marked as required, show it here!
	if (!empty($context['custom_fields_required']) && !empty($context['custom_fields']))
	{
		echo '
				<dl class="settings">';

		foreach ($context['custom_fields'] as $key => $field)
		{
			if ($field['show_reg'] > 1)
			{
				echo '
					<dt>
						<label ', !empty($field['is_error']) ? ' class="error"' : '', ' for="', $field['colname'], '">', $field['name'], ':</label>
						<span class="smalltext">', $field['desc'], '</span>
					</dt>
					<dd>', preg_replace_callback('~<(input|select|textarea) ~', static function ($matches) {
					global $context;

					return '<' . $matches[1] . ' tabindex="' . ($context['tabindex']++) . '"';
				}, $field['input_html']), '</dd>';

				// Drop this one so we don't show the additional information header unless needed
				unset($context['custom_fields'][$key]);
			}
		}

		echo '
				</dl>';
	}

	echo '
			</fieldset>
		</div>';

	// If we have either of these, show the extra group.
	if (!empty($context['profile_fields']) || !empty($context['custom_fields']))
	{
		echo '
		<div class="separator"></div>
		<h2 class="category_header">', $txt['additional_information'], '</h2>
		<div class="content">
			<fieldset>
				<dl class="settings" id="custom_group">';
	}

	if (!empty($context['profile_fields']))
	{
		// Any fields we particularly want?
		foreach ($context['profile_fields'] as $key => $field)
		{
			if ($field['type'] === 'callback')
			{
				if (isset($field['callback_func']) && function_exists('template_profile_' . $field['callback_func']))
				{
					$callback_func = 'template_profile_' . $field['callback_func'];
					$callback_func();
				}
			}
			else
			{
				echo '
					<dt>
						<label', !empty($field['is_error']) ? ' class="error"' : '', '>', $field['label'], ':</label>';

				// Does it have any subtext to show?
				if (!empty($field['subtext']))
					echo '
						<span class="smalltext">', $field['subtext'], '</span>';

				echo '
					</dt>
					<dd>';

				// Want to put something in front of the box?
				if (!empty($field['preinput']))
					echo '
						', $field['preinput'];

				// What type of data are we showing?
				if ($field['type'] === 'label')
					echo '
						', $field['value'];
				// Maybe it's a text box - very likely!
				elseif (in_array($field['type'], array('int', 'float', 'text', 'password')))
					echo '
						<input type="', $field['type'] === 'password' ? 'password' : 'text', '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" tabindex="', $context['tabindex']++, '" ', $field['input_attr'], ' class="input_', $field['type'] === 'password' ? 'password' : 'text', '" />';
				// Maybe it's an html5 input
				elseif (in_array($field['type'], array('url', 'search', 'date', 'email', 'color')))
					echo '
						<input type="', $field['type'], '" name="', $key, '" id="', $key, '" size="', empty($field['size']) ? 30 : $field['size'], '" value="', $field['value'], '" ', $field['input_attr'], ' class="input_', $field['type'] === 'password' ? 'password' : 'text', '" />';
				// You "checking" me out? ;)
				elseif ($field['type'] === 'check')
					echo '
						<input type="hidden" name="', $key, '" value="0" /><input type="checkbox" name="', $key, '" id="', $key, '" ', !empty($field['value']) ? ' checked="checked"' : '', ' value="1" tabindex="', $context['tabindex']++, '" class="input_check" ', $field['input_attr'], ' />';
				// Always fun - select boxes!
				elseif ($field['type'] === 'select')
				{
					echo '
						<select name="', $key, '" id="', $key, '" tabindex="', $context['tabindex']++, '">';

					if (isset($field['options']))
					{
						// Is this some code to generate the options?
						if (!is_array($field['options']))
							$field['options'] = eval($field['options']);

						// Assuming we now have some!
						if (is_array($field['options']))
						{
							foreach ($field['options'] as $value => $name)
							{
								echo '
							<option value="', $value, '" ', $value == $field['value'] ? 'selected="selected"' : '', '>', $name, '</option>';
							}
						}
					}

					echo '
						</select>';
				}
				// Something to end with?
				if (!empty($field['postinput']))
					echo '
							', $field['postinput'];

				echo '
					</dd>';
			}
		}
	}

	// Are there any custom fields?
	if (!empty($context['custom_fields']))
	{
		foreach ($context['custom_fields'] as $field)
		{
			if ($field['show_reg'] < 2)
				echo '
					<dt>
						<strong', !empty($field['is_error']) ? ' class="error"' : '', '>', $field['name'], ':</strong>
						<span class="smalltext">', $field['desc'], '</span>
					</dt>
					<dd>', $field['input_html'], '</dd>';
		}
	}

	// If we have either of these, close the list like a proper gent.
	if (!empty($context['profile_fields']) || !empty($context['custom_fields']))
	{
		echo '
				</dl>
			</fieldset>
		</div>';
	}

	if ($context['require_agreement'] || $context['require_privacypol'])
	{
		echo '
			<fieldset class="content">';

		if ($context['require_agreement'])
		{
			echo '
				<h2 class="category_header">', $txt['registration_agreement'], '</h2>
				<div id="agreement_box">
					', $context['agreement'], '
				</div>
				<label for="checkbox_agreement">
					<input type="checkbox" name="checkbox_agreement" id="checkbox_agreement" value="1"', ($context['registration_passed_agreement'] ? ' checked="checked"' : ''), ' tabindex="', $context['tabindex']++, '" />
					', $txt['checkbox_agreement'], '
				</label>';
		}

		if ($context['require_privacypol'])
		{
			echo '
				<h2 class="category_header">', $txt['registration_privacy_policy'], '</h2>
				<div id="privacypol_box">
					', $context['privacy_policy'], '
				</div>
				<label for="checkbox_privacypol">
					<input type="checkbox" name="checkbox_privacypol" id="checkbox_privacypol" value="1"', ($context['registration_passed_privacypol'] ? ' checked="checked"' : ''), ' tabindex="', $context['tabindex']++, '" />
					', $txt['checkbox_privacypol'], '
				</label>';
		}

		if (!empty($context['languages']))
		{
			echo '
				<br />
				<select id="agreement_lang" class="input_select">';
			foreach ($context['languages'] as $key => $val)
			{
				echo '
					<option value="', $key, '"', !empty($val['selected']) ? ' selected="selected"' : '', '>', $val['name'], '</option>';
			}
			echo '
				</select>';
		}

		echo '
			</fieldset>';
	}

	echo '
		<div class="submitbutton centertext">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="', $context['register_token_var'], '" value="', $context['register_token'], '" />
			<input type="hidden" name="provider" value="', $context['provider'], '" />
			<button type="submit" class="linkbutton">
				<div class="extauth_', strtolower($context['provider']), '">
					<span style="margin-left:2.5em"> ', $txt['register_with'], ' ', $context['provider'], '</span>
				</div>
			</button>
		</div>
	</form>';
}

/**
 * Add External Authentication template to the login template
 */
function template_extauth_login_below()
{
	global $context, $scripturl, $txt;

	if (empty($context['enabled_providers']))
	{
		return '';
	}

	echo '
	<div class="login">
		<h2 class="category_header hdicon cat_img_login">
			', $txt['extauth_login'], '
		</h2>
		<div class="roundframe">
			<ul class="extauth_icons">';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
				<li>
					<a href="', $scripturl, '?action=extauth;provider=', $provider, ';', $context['session_var'], '=', $context['session_id'], '" title="', $txt['login_with'], $provider, '" >
						<div class="extauth_icon extauth_', strtolower($provider), '"></div>
						<div class="centertext">', $provider, '</div>
					</a>
				</li>';
	}

	echo '
			</ul>
		</div>
	</div>';
}

/**
 * Add External Authentication template to the register template
 */
function template_extauth_register_above()
{
	global $context, $scripturl, $txt;

	echo '
		<h2 class="category_header">
			', $txt['extauth_register'], '
		</h2>
		<div class="roundframe">
			<p class="description">', $txt['extauth_register_desc'], '</p>
			<ul class="extauth_icons">';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
				<li>
					<a href="', $scripturl, '?action=extauth;provider=', $provider, ';', $context['session_var'], '=', $context['session_id'], '" title="', $txt['register_with'], $provider, '" >
						<div class="extauth_icon extauth_', strtolower($provider), '"></div>
						<div class="centertext">', $provider, '</div>
					</a>
				</li>';
	}

	echo '
			</ul>
		</div>
		<br />';
}

/**
 * Adds small icons next to the login bar
 */
function template_th_extauth_icons()
{
	global $context, $scripturl, $txt;

	echo '
	<div id="extauth_th_icons">
		<ul class="extauth_th_icons">';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
			<li>
				<a href="', $scripturl, '?action=extauth;provider=', $provider, ';', $context['session_var'], '=', $context['session_id'], '">
					<div class="extauth_th_icon extauth_', strtolower($provider), '" title="', $txt['login_with'], $provider,'"></div>
				</a>
			</li>';
	}

	echo '
		</ul>
	</div>';
}
