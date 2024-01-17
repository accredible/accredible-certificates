=== Accredible Certificates & Open Badges ===
Contributors: accredible
Donate link: https://www.accredible.com/
Tags: accredible, certificate, certificates, digital certificates, online course, lms, learning management system, e-learning, elearning, badges, badge, open badge, mozilla open badge, blockchain, blockchain credential, credential, credentials
Requires at least: 3.0.1
Tested up to: 6.4
Stable tag: 1.4.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Certificates, open badges and blockchain credentials. Create, update and manage them on your Wordpress site.

== Description ==

The Accredible platform enables organizations to create, manage and distribute digital credentials as digital certificates, open badges and blockchain credentials.

An example digital certificate and badge can be viewed here: https://www.credential.net/10000005

This plugin enables you to issue dynamic, digital certificates or open badges on your Wordpress instance.

A video walkthrough of the plugin can be viewed here: https://youtu.be/s5krPN6GvTY

Benefits of digital credentials:

* Every time your recipients share their credentials, your brand spreads across the web.
* View analytics on your alumni.
* Create a directory of your graduates.
* Unlimited certificate and badge designs.
* Easy sharing, exporting and printing.
* Make sure people can't lie about taking your course.


== Example Output ==
![Example Digital Certificate](https://s3.amazonaws.com/accredible-cdn/example-digital-certificate.png)

![Example Open Badge](https://s3.amazonaws.com/accredible-cdn/example-digital-badge.png)

== Installation ==

1. Visit https://accredible.com to obtain an API key
2. Install the plugin
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the plugin settings and input your API key
5. Add the widget if desired. It will show a list of certificates or badges for a currently signed in user.
6. Add the shortcode [accredible_credential image="true" limit="10" style="true"] to a page or post if desired. It will show certificates and badges for a currently signed in user.

Manually creating certificates:

1. Go to the 'Certificates & Badges' page in the Wordpress admin menu.
2. On the list of your users, select which students you would like to issue certificates to and then select a group.
3. Click 'Create Credentials'

![Certificates Admin](https://s3.amazonaws.com/accredible-moodle-instructions/wordpress/certificates-admin.png)


== Frequently Asked Questions ==

= How do I get an API key? =

Visit https://accredible.com to obtain a free API key.

= How can I show certificates or badges to my users? =

You can use the widget or shortcode to display badges or certificates that belong to the current Wordpress user.

The shortcode is: [accredible_credential] but it accepts a number of options: [accredible_credential image="true" limit="10" style="true"].

= Can you add support for another Wordpress LMS or theme? =

Sure, just post an issue and we'll get to work: https://github.com/accredible/accredible-certificates/issues

== Screenshots ==

1. Digital Certificate
2. Digital Open Badge
3. Create digital certificates
4. Responsive certificate designs
5. Marketing click throughs and impressions
6. Example Google certificate.
7. Shortcode output.
8. Widget output.

== Changelog ==

= 1.4.9 =
Fix issue in API Settings page

= 1.4.8 =
Fix issue with uppercase emails

= 1.4.7 =
Fix version info of the plugin

= 1.4.6 =
Update HTTP request headers of the API

= 1.4.5 =
Test against latest WP version.

= 1.4.4 =
Shortcode no longer incorrectly outputs content.

= 1.4.3 =
Use the Wordpress user's full name rather than nicename.

= 1.4.2 =
Add the ability to search through Wordpress users.

= 1.4.1 = 
Limit the certificate users page to a maximum of 50.

= 1.4.0 =
Added widget and shortcode to display/list recipient certificates & badges.

= 1.3.0 =
Update permissions required to issue certificates & badges.

= 1.2.1 =
Added better debugging for easier customer support.

= 1.2.0 =
Use the Wordpress user full name and a few small updates.

= 1.1.2 = 
Fix bug with second drop down group selection not always working.

= 1.1.1 =
Better documentation.

= 1.1.0 =
Support PHP 5.4.

= 1.0.1 =
Bug fixes.

= 1.0.0 =
Allow creation and management of certificates and badges for any users - not just Academy Theme. 

= 0.2.0 =
Replace Curl requirement so that plugin works on Windows hosts.

= 0.1.5 =
More bug fixes and improvements.

= 0.1.4 =
Bug fixes and improvements.

= 0.1.3 =
Pass through course link on certificate creation.

= 0.1.2 =
Add course completion to certificates table.

= 0.1.1 =
Bug fixes.

= 0.1.0 =
First version.
