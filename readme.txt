=== VinCSS FIDO2 Login ===
Contributors: VinCSS Internet Security Services Limited Liability Company
Tags: passwordless, oauth2, u2f, otp, 2fa, uaf, fido, fido2, webauthn, login, security, password, authentication, vincss, security, authentication 
Requires at least: 5.0
Tested up to: 5.8.2
Stable tag: 1.0.2
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

VinCSS FIDO2 Login transform applications to passwordless authentication, improve user experience and protect end users with highest security standard.

== Description ==
VinCSS FIDO2 Login helps you replace your passwords with devices like USB security key, fingerprint, Windows Hello, FaceID/TouchID....Plugin also support 2FA and OAuth2 Protocol.
When you enable plugin on your site, you can log in without password or 2FA at the same time.

 
= Features =
* **FIDO2 Passwordless Authentication**
* **Two-factor authentication**
* **Enable OAuth2 authentication for your site**


VinCSS FIDO2 Login is a plug-in for WordPress to enable FIDO2, OTP and OAuth2 on your site.

= FIDO2 Passwordless Authentication =
 When you enable VinCSS FIDO2 Login on your site, you can log in with a simple action.
 **PHP extensions php-gmp, php-mbstring are required.**
 **FIDO2 Passwordless Authentication requires HTTPS connection or `localhost`**

= Two-factor authentication =
This plugin can be configured for any TOTP-based authentication method like Google Authenticator, Microsoft Authenticator, etc.

= OAuth2 =
VinCSS FIDO2 Login plugin allows login with your VinCSS FIDO2 Cloud or other custom OAuth2 providers.

== Frequently Asked Questions ==

= What languages does this plugin support? =
This plugin supports Vietnamese, English. If you are using WordPress in none of those languages, English will be displayed as default language.

= Which browsers support? =
Chrome 67+, Firefox 60+, Edge 18+, Chrome 85+ on Android.
iOS: Safari
macOS: Chrome 67, Firefox 60, Safari 14+ on BigSur.
To use FaceID or TouchID, you need to use iOS/iPadOS 14+.

= Do you store my facial or confidential data? =
No, the plugin never scans your finger, face or stores any representation of it. Data is provided by the device, which only tells plugin if your person was recognized or not.

= What should I do if the plugin could not work? =

Make sure you have installed the php-gmp, php-mbstring PHP extension and enable Windows Hello PIN. Plugin only works with https or localhost.


= Support =
Customized solutions and Support options are available. Email us at	fido2product@vincss.net.

== Screenshots ==

1. Settings page
2. Login page
3. OAuth2
4. Profile page
5. Verifying
6. OTP


== Changelog ==
v1.0.2
*  Resolve conflict in PHP 7.2

v1.0.1
*  Prevent WordPress plugin conflicts
