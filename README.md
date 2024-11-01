# VinCSS FIDO2 Login

## Introduction
VinCSS FIDO2 Login helps you replace your passwords with devices like USB security key, fingerprint, Windows Hello, FaceID/TouchID....Plugin also support 2FA OTP and Oauth2 Protocol.<br/>
When you enable plugin on your site, you can log in without password or 2FA at the same time
## Features

- FIDO2 Passwordless Authentication
- Two-factor authentication
- Enable OAuth2 authentication for your site

## Description 
- FIDO2 Passwordless Authentication<br/>
 When you enable VinCSS FIDO2 Login on your site, you can log in with a simple action.<br/>
 **PHP extensions php-gmp, php-mbstring are required.**<br/>
 **FIDO2 Passwordless Authentication requires HTTPS connection or `localhost`**

- Two-factor authentication<br/>
This plugin can be configured for any TOTP-based authentication method like Google Authenticator, Microsoft Authenticator, etc.

- OAuth2<br/>
VinCSS FIDO2 Login plugin allows login with your VinCSS FIDO2 Cloud or other custom OAuth2 providers.

## Frequently Asked Questions 

- What languages does this plugin support?<br/>
This plugin supports Vietnamese, English. If you are using WordPress in none of those languages, English will be displayed as default language.

- Which browsers support?<br/> 
Chrome 67+, Firefox 60+, Edge 18+, Chrome 85+ on Android.<br/>
iOS: Safari.<br/>
macOS: Chrome 67, Firefox 60, Safari 14+ on BigSur.<br/>
To use FaceID or TouchID, you need to use iOS/iPadOS 14+.

- Do you store my facial or confidential data?<br/>
No, the plugin never scans your finger, face or stores any representation of it. Data is provided by the device, which only tells plugin if your person was recognized or not.

- What should I do if the plugin could not work?<br/>
Make sure you have installed the php-gmp, php-mbstring PHP extension and enable Windows Hello PIN. Plugin only work with https or localhost

## Support ##
Customized solutions and Support options are available. Email us at	fido2product@vincss.net.


## Changelog ##
v1.0.2
*  Resolve conflict in PHP 7.2

v1.0.1
*  Prevent WordPress plugin conflicts
