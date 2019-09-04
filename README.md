# External Authentication

## Summary
Allows your users to connect with one click to your ElkArte Forum using their social network accounts.

Supports social networks: Facebook, Twitter, LinkedIn, Google, Yahoo, GitHub

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
**Existing users** should "Connect" their current site account with each of the social networks they intend to use.  They can do this from their profile page Modify Profile -> Connected Accounts. Once done they can simply click the appropriate social icon on the login page and be logged in to the site.

**New users** can select a social login icon which will begin a registration process that will Connect a new site account to the chosen social site.  They will still have to accept your site agreement and any required profile fields you have enabled including email and userid.

## Provider Setup

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

### Setting up Twitter

* Go to https://dev.twitter.com/apps/new
* Create a new application
* Enter a Callback URL: http://YOUR-SITE-URL/hybridauth/endpoint?hauth.done=Twitter
* Copy your Consumer Key and Consumer Secret to the corresponding fields in the addon settings
* Check Allow this application to be used to Sign in with Twitter.
* Under Permissions select Read only access.

### Setting up Google

* Go to https://code.google.com/apis/console/
* Create a new project, you will then see the API Manager for you new Project
* Select OAuth consent screen.  The consent screen will be shown to users whenever you request access to their
  private data using your client ID. Fill out any required fields such as the site url and logo
* Select Credentials -> Create Credentials -> OAuth Client ID.
* In Client ID settings:
    * Application Type is Web Application
    * Authorized Redirect URIs - enter the Authentication URL http://YOUR-SITE-URL/hybridauth/endpoint?hauth.done=Google
* Copy your Client ID and Client secret to corresponding fields in the addon settings

### Setting up LinkedIn

* Go to https://www.linkedin.com/secure/developer
* Create new application
    * Under default permissions select r_basicprofile and r_emailaddress
    * The Oauth2 Authorized Redirect URLs: http://YOUR-SITE-URL/hybridauth/endpoint?hauth.done=Linkedin
* Copy the Client ID and Client secret to corresponding fields in the addon settings

### Setting up Yahoo!

* Go to https://developer.yahoo.com/apps/
* Create new Application
    * Fill out the project information
    * Use Web Application for the application type
    * Under API permissions choose Profile -> Read Public
    * Callback Domain - enter http://YOUR-SITE-URL
* Copy the Consumer Key and Consumer Secret to corresponding fields in the addon settings
