<?php
global $boardurl;

// Admin / Profile / Etc. strings
$txt['extauth_master'] = 'Enable the addon';
$txt['extauth_loginbar'] = 'Show login icons next to the login bar (if enabled)';
$txt['extauth_noemail'] = 'Don\'t require email validation if registering with social media.';
$txt['extauth_missing_requirements'] = '<p class="error">You must have both the Curl and JSON modules enabled on your server.</p>';
$txt['connected_accounts'] = 'Connected Accounts';
$txt['connect_accounts'] = 'Connect Accounts';
$txt['provider_services'] = 'OAuth Providers';
$txt['extauth_login'] = 'Login with your Social Account';
$txt['extauth_register'] = 'Register using your Social Account';
$txt['extauth_register_desc'] = 'You can sign-up using an <strong>existing</strong> social network account, or continue below to register for a new account here.';
$txt['connected_accounts_desc'] = 'These are external accounts that can be connected to your account on this site. By linking to an external account, you can use that service to login here.';
$txt['disconnect'] = 'Disconnect Account';
$txt['connect'] = 'Connect using';
$txt['register_with'] = 'Register using';
$txt['extauth_reg_notice'] = 'If you have an <strong>existing account</strong> on this site, then <a class="linkbutton" href="' . $boardurl . '/index.php?action=login">Login Here</a> and make connections (Modify Profile -> Connect Accounts) to the social networks you wish to use.';
$txt['extauth_register'] = 'Register using external account';
$txt['provider_services_settings'] = 'Provider Account Keys';
$txt['provider_services_settings_desc'] = 'Select the social networks that you would like to use by ticking their checkboxes.
<br />
Each social network will require that you register your website to their API before being able to use their services. These APIs ensure that users are logging into the correct Web site and allows it to send the user back to this Web site after successfully authenticating.
<br />
To be able to use this addon, you must therefore register your website with each social network that you enable. This process is straightforward, takes only a couple of minutes and has to be done only once for each provider.
<br />
Use the help [?] button for the steps required for each social network and the registration button to take you to that providers API registration screen.';

// Error responses from hybridauth
$txt['extauth_error_0'] = 'Unspecified error';
$txt['extauth_error_1'] = 'HybridAuth configuration error';
$txt['extauth_error_2'] = 'Provider not properly configured';
$txt['extauth_error_3'] = 'Unknown or disabled provider';
$txt['extauth_error_4'] = 'Missing provider application credentials (your application id, key or secret)';
$txt['extauth_error_5'] = 'Authentication failed';
$txt['extauth_error_6'] = 'User profile request failed';
$txt['extauth_error_7'] = 'User not connected to the provider';
$txt['extauth_error_8'] = 'Provider does not support this feature';

// All of the providers
$txt['ext_enable_facebook'] = 'Enable Facebook Login';
$txt['ext_key_facebook'] = 'Facebook application id';
$txt['ext_secret_facebook'] = 'Facebook application secret';
$txt['ext_api_url_facebook'] = '<a class="linkbutton" href="https://developers.facebook.com/apps" target="_blank">Key Registration</a>';
$txt['ext_api_url_facebook_help'] = 'Create a new app 
Select Basic settings, Fill out required fields such as the application name and description. Enter your site name as the App Domain 
Select Add platform and choose website, enter the url to your site 
Select + Add Product Select Facebook Login Select Website 
Select Facbook Login Settings Enter the Valid OAuth redirect as ' . $boardurl . '/sources/ext/hybridauth?hauth_done=Facebook 
Copy your App ID and App Secret to corresponding fields in the addon settings';

$txt['ext_enable_google'] = 'Enable Google Login';
$txt['ext_key_google'] = 'Google client id';
$txt['ext_secret_google'] = 'Google client secret';
$txt['ext_api_url_google'] = '<a class="linkbutton" href="https://code.google.com/apis/console/" target="_blank">Key Registration</a>';
$txt['ext_api_url_google_help'] = 'Start a new project, you will then see the API Manager for you new Project. 
Select OAuth consent screen.  The consent screen will be shown to users whenever you request access to their 
private data using your client ID. Fill out any required fields such as the site url and logo 
Select Credentials -> Create Credentials -> OAuth Client ID. 
Select Web application, enter this URL in the [Authorized redirect URIs] ' . $boardurl . '/sources/ext/hybridauth?hauth.done=Google 
Copy and past the created application credentials (ID and Secret) to the corresponding fields in the addon settings. 
Lastly enable the GooglePlus+ API for this application.';

$txt['ext_enable_twitter'] = 'Enable Twitter Login';
$txt['ext_key_twitter'] = 'Twitter consumer key';
$txt['ext_secret_twitter'] = 'Twitter consumer secret';
$txt['ext_api_url_twitter'] = '<a class="linkbutton" href="https://dev.twitter.com/apps" target="_blank">Key Registration</a>';
$txt['ext_api_url_twitter_help'] = 'Create a new app. 
Fill out any required fields such as the application name and description. 
Put your website domain in the Website field. 
Provide this URL as the Callback URL for your application: ' . $boardurl . '/sources/ext/hybridauth?hauth.done=Twitter 
Once you have registered, copy and past the created application credentials (Consumer Key and Secret) to the corresponding fields in the addon settings. 
Check Allow this application to be used to Sign in with Twitter. 
Under Permissions select Read only access.';

$txt['ext_enable_linkedin'] = 'Enable LinkedIn Login';
$txt['ext_key_linkedin'] = 'LinkedIn client id';
$txt['ext_secret_linkedin'] = 'LinkedIn client secret';
$txt['ext_api_url_linkedin'] = '<a class="linkbutton" href="https://www.linkedin.com/secure/developer" target="_blank">Key Registration</a>';
$txt['ext_api_url_linkedin_help'] = 'Create a new application. 
Fill out all required fields including logo, phone number, application type Other, etc 
Under default permissions select r_basicprofile and r_emailaddress 
Provide this URL as the Oauth2 Authorized Redirect URLs: ' . $boardurl . '/sources/ext/hybridauth?hauth.done=Linkedin 
Copy and past the created application credentials (Client ID and Secret) to the corresponding fields in the addon settings.';

$txt['ext_enable_yahoo'] = 'Enable Yahoo Login';
$txt['ext_key_yahoo'] = 'Yahoo client id';
$txt['ext_secret_yahoo'] = 'Yahoo client secret';
$txt['ext_api_url_yahoo'] = '<a class="linkbutton" href="https://developer.yahoo.com/apps/" target="_blank">Key Registration</a>';
$txt['ext_api_url_yahoo_help'] = 'Create a new application. 
Fill out all required fields.  Use Web Application for the application type.  Under API permissions choose Profile -> Read Public 
Provide this URL for the Callback Domain: ' . $boardurl . ' 
Once you have registered, copy and paste the application credentials to the corresponding fields in the addon settings.';

$txt['ext_enable_github'] = 'Enable GitHub Login';
$txt['ext_key_github'] = 'GitHub client id';
$txt['ext_secret_github'] = 'GitHub client secret';
$txt['ext_api_url_github'] = '<a class="linkbutton" href="https://github.com/settings/applications/new" target="_blank">Key Registration</a>';
$txt['ext_api_url_github_help'] = 'Register a new OAuth application. 
Enter the Application Name, and your website home URL. 
Use the following for the Authorization callback URL ' . $boardurl . '/sources/ext/hybridauth?hauth.done=Github 
Enter the client id/secret to the corresponding fields in the addon settings';
