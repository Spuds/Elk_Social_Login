# External Authentication

## Summary
Allows your users to connect with one click to your ElkArte Forum using their social network accounts.

Supports social networks: Facebook, Twitter, LinkedIn, Google, Yahoo, GitHub, Amazon and more

## Description
The settings for the addon are under Admin -> Members -> Registration -> OAuth Providers

Each social network will **require that you** create an external application linking your Web site to their api. These external OAuth applications ensure that users are logging into the correct Web site and allows it to send the user back to the correct Web site after successfully authenticating.

The link to setup your OAuth accounts for these sites is provided in the Admin Panel along with general steps of what to do for each API.  Most important will be the Redirect URIs which is provided in the help for each.

## Features

* Allows a single ElkArte user profile to be connected to multiple social provider accounts
* Follows Elkarte's registration workflow but streamlined with provider authorization.
* Attempt to fetch some profile information from the social site during registartion (avatar, gender, website, etc) access depends on each social site.
* Allows users to authorize / de-authorize providers in their account settings (Modify Profile -> Connected Accounts)
* Allows admin to selectively enable / disable providers
* Allows for one button login to connected accounts

## New or Existing User
**Existing users** can "Connect" their current account with as many of the social networks they intend to use.  They can do this from their profile page Modify Profile -> Connected Accounts. Once done they can simply click the appropriate social icon on the login page and be logged in to the site.

**New users** can select a social login icon which will begin a registration process that will create a new site account using the informatino provided by the chosen social/provider site.  They will still have to accept your site agreement and any required profile fields you have enabled including email and userid.  This simplifies the overall registartion process for you users.

## Provider Setup

### Setting up Twitter

* Go to https://dev.twitter.com/apps/new, you will need to sign up for a developer account
* Create a new application
* Fill out any required fields such as the application name, description, TOS links
* Check Allow this application to be used to Sign in with Twitter.
* Enter this generic Callback URL: https://YOUR-SITE-URL/hybridauth
* Copy your Consumer Key and Consumer Secret to the corresponding fields in the addon settings
* Under Permissions select Read only access and require Email

### Setting up Google

* Go to https://code.google.com/apis/console/
* Start a new project, you will then see the API Manager for you the nes Project. 
* Select OAuth consent screen.  This consent screen will be shown to users whenever you request access to their data using your client ID. 
* Fill out all required fields such as the site url, contact email, and logo.  You also need to enable the email and profile "scopes".
* Select Credentials -> Create Credentials -> OAuth Client ID. 
* Select Web application, enter the following for the [Authorized redirect URIs] https://YOUR-SITE-URL/hybridauth/endpoint?hauth.done=Google
* Copy and past the created application credentials (ID and Secret) to the corresponding fields in the addon settings.

### Setting up Yahoo!

* See https://developer.yahoo.com/sign-in-with-yahoo/ for full documentation.
* Create a new application. 
* Fill out all required fields.  
* Provide the following for the Redirect URL: https://YOUR-SITE-URL/sources/ext/hybridauth?hauth.done=Yahoo
* Under OAuth Client Type choose Confidential Client.  
* Under API Permission choose OpenID and then Email/Profile.
* Once you have registered the app, copy and paste the application credentials to the corresponding fields in the addon settings.

### Setting up GitHub

* Go to https://github.com/settings/applications/new
* Register a new OAuth application. 
* Enter an Application Name, and your website homepage URL. 
* Use the following for the Authorization callback URL: https://YOUR-SITE-URL//sources/ext/hybridauth?hauth.done=Github 
* Enter the client id/secret to the corresponding fields in the addon settings

### Setting up Amazon

* See https://developer.amazon.com/docs/login-with-amazon/web-docs.html
* Sign up for an Amazon Developer ID if you do not have one.
* Create a Security Profile for LWA (Login With Amazon).
* Fill in the required fields, site name, privacy policy link, logo image
* Under the new security profile, Web settings, use the following for the Allowed Return URL: https://YOUR-SITE-URL/sources/ext/hybridauth?hauth.done=Amazon 
* Enter the client id/secret to the corresponding fields in the addon settings';

### Setting up Facebook

* Go to https://developers.facebook.com/apps
* Create a new App
* Select Basic settings
    * Fill out required fields such as the application name and description.
    * Enter your site name as the App Domain
    * Select Add platform and choose website, enter the url to your site
* Select + Add Product
    * Select Facebook Login
    * Select Website
* Select Facbook Login Settings
    * Enter the Valid OAuth redirect http://YOUR-SITE-URL/hybridauth/endpoint?hauth_done=Facebook
	
### Setting up LinkedIn

* Go to https://www.linkedin.com/secure/developer
* Create new application
    * Under default permissions select r_basicprofile and r_emailaddress
    * The Oauth2 Authorized Redirect URLs: http://YOUR-SITE-URL/hybridauth/endpoint?hauth.done=Linkedin
* Copy the Client ID and Client secret to corresponding fields in the addon settings
