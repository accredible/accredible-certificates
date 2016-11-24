=== Accredible Certificates ===
Contributors: accredible
Donate link: https://accredible.com/
Tags: certificate, certificates, online course, lms, badges, badges, open badge
Requires at least: 3.0.1
Tested up to: 4.6.1
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Issue dynamic, digital certificates or open badges on your Wordpress site.

== Description ==

The Accredible platform enables organizations to create, manage and distribute digital credentials as digital certificates or open badges.

An example digital certificate and badge can be viewed here: https://www.credential.net/10000005

This plugin enables you to issue dynamic, digital certificates or open badges on your Wordpress instance.

If you're using Academy Theme then we automatically create groups on Accredible for each course and automatically generate certificates or badges on an hourly basis.

== Example Output ==
![Example Digital Certificate](https://s3.amazonaws.com/accredible-cdn/example-digital-certificate.png)

![Example Open Badge](https://s3.amazonaws.com/accredible-cdn/example-digital-badge.png)

== Installation ==

1. Visit https://accredible.com to obtain an API key
2. Install the plugin
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to the plugin settings and input your API key


If you're using Academy Theme and would like students to be able to access certificates then please:
1. Access the Theme Editor from the Administration > Appearance > Editor menu
2. Open module_form.php from the right sidebar.
3. Replace the entire file contents with:

	<form action="<?php echo themex_url(true, ThemexCore::getURL('register')); ?>" method="POST">
		<?php if(!ThemexCourse::isSubscriber()) { ?>
		<a href="#" class="button medium submit-button left"><?php _e('Subscribe Now', 'academy'); ?></a>
		<input type="hidden" name="course_action" value="subscribe_user" />
		<input type="hidden" name="user_redirect" value="<?php echo intval(reset(ThemexCourse::$data['plans'])); ?>" />
		<?php } else if(!ThemexCourse::isMember()) { ?>
			<?php if(ThemexCourse::$data['status']!='private' && ThemexCourse::$data['capacity']>=0) { ?>
			<a href="#" class="button medium price-button submit-button left">		
				<?php if(ThemexCourse::$data['status']=='premium' && ThemexCourse::$data['product']!=0) { ?>
				<span class="caption"><?php _e('Take This Course', 'academy'); ?></span>
				<span class="price"><?php echo ThemexCourse::$data['price']['text']; ?></span>
				<?php } else { ?>
				<?php _e('Take This Course', 'academy'); ?>
				<?php } ?>
			</a>
			<input type="hidden" name="course_action" value="add_user" />
			<input type="hidden" name="user_redirect" value="<?php echo ThemexCourse::$data['ID']; ?>" />
			<?php } ?>
		<?php } else { ?>
			<?php if(!ThemexCore::checkOption('course_retake')) { ?>
			<a href="#" class="button secondary medium submit-button left"><?php _e('Unsubscribe Now', 'academy'); ?></a>
			<input type="hidden" name="course_action" value="remove_user" />
			<?php } ?>
			<?php if($result = Accredible_Certificate::hasCertificate(ThemexCourse::$data['ID'], ThemexUser::$data['user']['ID'])) { ?>
			<a href="<?php echo "https://www.accredible.com/".$result; ?>" target="_blank" class="button medium certificate-button"><?php _e('View Certificate', 'academy'); ?></a>
			<?php } ?>
		<?php } ?>
		<input type="hidden" name="course_id" value="<?php echo ThemexCourse::$data['ID']; ?>" />
		<input type="hidden" name="plan_id" value="<?php echo intval(reset(ThemexCourse::$data['plans'])); ?>" />	
		<input type="hidden" name="nonce" class="nonce" value="<?php echo wp_create_nonce(THEMEX_PREFIX.'nonce'); ?>" />
		<input type="hidden" name="action" class="action" value="<?php echo THEMEX_PREFIX; ?>update_course" />
	</form>

Then click 'Update file'

Manually creating certificates:

1. Go to the 'Certificates & Badges' page in the Wordpress admin menu.
2. On the list of your users, select which students you would like to issue certificates to and then select a group.
3. Click 'Create Credentials'

![Certificates Admin](https://s3.amazonaws.com/accredible-moodle-instructions/wordpress/certificates-admin.png)

Automatically creating certificates:

1. Go to the plugin settings and check 'Automatically Issue Certificate upon Course Completition'
2. When a user completed a course they will be automatically issued a certificate/badge.


== Frequently Asked Questions ==

= How do I get an API key? =

Visit https://accredible.com to obtain a free API key.

= Can you add support for another Wordpress LMS or theme? =

Sure, just post an issue and we'll get to work: https://github.com/accredible/accredible-certificates/issues

== Screenshots ==

1. Digital Certificate
2. Digital Open Badge
3. Create digital certificates

== Changelog ==

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
