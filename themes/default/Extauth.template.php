<?php

/**
 * @package "ExternalAuth" External Authentication Addon for Elkarte
 * @author Spuds
 * @copyright (c) 2017 Spuds
 * @license No derivative works. No warranty, explicit or implicit, provided.
 * The Software is provided under an AS-IS basis, Licensor shall never, and without any limit,
 * be liable for any damage, cost, expense or any other payment incurred by Licensee as a result
 * of Software’s actions, failure, bugs and/or any other interaction.
 *
 * @version 1.0.3
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
		if (in_array($provider, $context['connected_providers']))
		{
			echo '
			<dd>
				<a class="linkbutton alert" href="', $scripturl, '?action=extauth;provider=', strtolower($provider), ';sa=deauth;member=', $context['member']['id'], ';', $context['session_var'], '=', $context['session_id'], '">
					<i class="fa fa-lg fa-', strtolower($provider), '"></i>', ' ', $txt['disconnect'], '
				</a>
			</dd>';
		}
		// Make a new connection
		else
		{
			echo '
			<dd>
				<a class="linkbutton" href="', $scripturl, '?action=extauth;provider=', strtolower($provider), ';sa=auth;member=', $context['member']['id'], ';', $context['session_var'], '=', $context['session_id'], '">
					<i class="fa fa-lg fa-', strtolower($provider), '"></i>', ' ', $txt['connect'], ' ', $provider, '
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
			echo '
				<li>', $error, '</li>';

		echo '
			</ul>
		</div>';
	}

	echo '
		<div class="content">
			<input type="password" name="autofill_honey_pot" style="display:none" />
			<fieldset>
				<dl class="register_form">
					<dt>
						<strong><label for="username">', $txt['username'], ':</label></strong>
					</dt>
					<dd>
						<input type="text" name="user" id="username" size="30" tabindex="', $context['tabindex']++, '" maxlength="25" value="', isset($context['username']) ? $context['username'] : '', '" class="input_text" placeholder="', $txt['username'], '" required="required" autofocus="autofocus" />
					</dd>
					<dt>
						<strong><label for="reserve1">', $txt['user_email_address'], ':</label></strong>
					</dt>
					<dd>
						<input type="email" name="email" id="reserve1" size="30" tabindex="', $context['tabindex']++, '" value="', isset($context['email']) ? $context['email'] : '', '" class="input_text" placeholder="', $txt['user_email_address'], '" required="required" />
					</dd>
					<dt>
						<strong><label for="allow_email">', $txt['allow_user_email'], ':</label></strong>
					</dt>
					<dd>
						<input type="checkbox" name="allow_email" id="allow_email" tabindex="', $context['tabindex']++, '" class="input_check" />
					</dd>
				</dl>';

	// If there is any field marked as required, show it here!
	if (!empty($context['custom_fields_required']) && !empty($context['custom_fields']))
	{
		echo '
				<dl class="register_form">';

		foreach ($context['custom_fields'] as $key => $field)
		{
			if ($field['show_reg'] > 1)
			{
				echo '
					<dt>
						<strong', !empty($field['is_error']) ? ' class="error"' : '', '><label for="', $field['colname'], '">', $field['name'], ':</label></strong>
						<span class="smalltext">', $field['desc'], '</span>
					</dt>
					<dd>', preg_replace_callback('~<(input|select|textarea) ~', create_function('$matches', '
						global $context;

						return \'<\' . $matches[1] . \' tabindex="\' . $context[\'tabindex\']++ . \'"\';
					'), $field['input_html']), '
					</dd>';

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
		<h3 class="category_header">', $txt['additional_information'], '</h3>
		<div class="content">
			<fieldset>
				<dl class="register_form" id="custom_group">';
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
						<strong', !empty($field['is_error']) ? ' class="error"' : '', '>', $field['label'], ':</strong>';

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
							foreach ($field['options'] as $value => $name)
								echo '
							<option value="', $value, '" ', $value == $field['value'] ? 'selected="selected"' : '', '>', $name, '</option>';
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

	if ($context['require_agreement'])
	{
		echo '
		<div id="agreement_box">
			', $context['agreement'], '
		</div>
		<div class="centertext">
			<input id="checkbox_agreement" name="checkbox_agreement" type="checkbox"', ($context['registration_passed_agreement'] ? ' checked' : ''), ' tabindex="', $context['tabindex']++, '">
			<label for="checkbox_agreement">', $txt['checkbox_agreement'], '</label>
		</div>';
	}

	echo '
		<div class="submitbutton centertext">
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="hidden" name="', $context['register_token_var'], '" value="', $context['register_token'], '" />
			<input type="hidden" name="provider" value="', $context['provider'], '" />
			<button type="submit" class="linkbutton">
				<i class="fa fa-lg fa-', strtolower($context['provider']), '"></i> ', $txt['register_with'], ' ', ucwords($context['provider']), '
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
					<a href="', $scripturl, '?action=extauth;provider=', strtolower($provider), ';', $context['session_var'], '=', $context['session_id'], '">
						<span class="fa-stack fa-3x">
							<i class="fa fa-square fa-stack-2x"></i>
							<i class="fa fa-stack-1x fa-inverse fa-', strtolower($provider), '"></i>
						</span>
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
		<h2 class="category_header hdicon cat_img_login">
			', $txt['extauth_register'], '
		</h2>
		<p class="infobox">', $txt['extauth_register_desc'], '</p>
		<div class="roundframe">
			<ul class="extauth_icons">';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
				<li>
					<a href="', $scripturl, '?action=extauth;provider=', strtolower($provider), ';', $context['session_var'], '=', $context['session_id'], '">
						<span class="fa-stack fa-3x">
							<i class="fa fa-square fa-stack-2x extstack"></i>
							<i class="fa fa-stack-1x fa-inverse fa-', strtolower($provider), '"></i>
						</span>
					</a>
				</li>';
	}

	echo '
			</ul>
		</div>';
}

/**
 * Adds small icons next to the login bar
 */
function template_th_extauth_icons()
{
	global $context, $scripturl;

	echo '
	<div id="extauth_th_icons">
		<ul>';

	foreach ($context['enabled_providers'] as $provider)
	{
		echo '
			<li style="display: inline">
				<a href="', $scripturl, '?action=extauth;provider=', strtolower($provider), ';', $context['session_var'], '=', $context['session_id'], '">
					<span class="fa-stack">
						<i class="fa fa-square fa-stack-2x extstack"></i>
						<i class="fa fa-stack-1x fa-inverse fa-', strtolower($provider), '"></i>
					</span>
				</a>
			</li>';
	}

	echo '
		</ul>
	</div>';
}
