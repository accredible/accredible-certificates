=== Accredible Certificates ===
Contributors: accredible
Donate link: https://accredible.com/
Tags: certificate, online course, lms
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 0.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Issue dynamic, digital certificates for online learning courses. It issues certificates for Wordpress sites that make use of the Academy Theme.

== Description ==

This module enables you to issue dynamic, digital certificates using the Accredible API on your Wordpress instance. They act as a replacement for the PDF certificates normally generated for your courses. An example output certificate can be viewed at: [https://accredible.com/example](https://accredible.com/example).

Currently the plugin is only compatible with Academy Theme.

== Installation ==

1. Visit https://accredible.com to obtain an API key
2. Ensure you have the Wordpress Academy Theme active
3. Install the plugin
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Go to the plugin settings and input your API key

Note: By default your Accredible account is in Sandbox mode and any certificates you create will need to be manually published via the Accredible API Dashboard before being emailed to students.

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

1. Go to the 'Certificates' page in the Wordpress admin menu 
2. On the list of your courses and students, select which students you would like to issue certificates to 
3. Click 'Create Certificates'

Automatically creating certificates:

1. Go to the plugin settings and check 'Automatically Issue Certificate upon Course Completition' 
2. When a user completed a course they will be automatically issued a certificate


== Frequently Asked Questions ==

= How do I get an API key? =

Visit https://accredible.com to obtain a free API key.

== Screenshots ==

1. This is a dynamic, digital certificate
2. This is the admin interface
3. You can automatically issue a certificate when a student completes a course

== Changelog ==

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
