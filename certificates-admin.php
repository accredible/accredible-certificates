<script type="text/javascript">
	function disableCertificateSubmitButton() {
		document.getElementById("create-credentials").disabled = 'true';
	}
</script>

<?php
/**
 * Display the certificates admin page.
 *
 * @package Accredible_Certificates
 */

defined( 'ABSPATH' ) || die;

require_once plugin_dir_path( __FILE__ ) . 'users_list.php';

$api_key = get_option( 'api_key' );
if ( empty( $api_key ) ) {
	// phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
	?>
	<div class="accredible-certificates-notice notice-info">
		<p><strong>Please ensure you have entered an API key in the plugin settings page.</strong></p>
		<p>To find your API key scroll down to the API key section on this page: <a href="https://dashboard.accredible.com/issuer/dashboard/department/api-settings" target="_blank">https://dashboard.accredible.com/issuer/dashboard/department/api-settings</a></p>
		<p>It looks like this:</p>
		<img src="<?php echo esc_url( ACCREDIBLE_CERTIFICATES_PLUGIN_URL . 'assets/images/example-apikey.png' ); ?>" style="width: 600px;" alt="">
	</div>
	<?php
	// phpcs:enable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
} else {
	$table_instance = new Users_List();

	// Display a notice if we have no groups with which to create credentials.
	if ( $table_instance->no_groups ) {
		echo '<div class="notice notice-error is-dismissible"> ';
		echo '<p><strong>Your Accredible account does not have any Groups. Please create a Group before trying to create Credentials: <a href="https://dashboard.accredible.com/issuer/dashboard/groups" target="_blank">https://dashboard.accredible.com/issuer/dashboard/groups</a></strong></p>';
		echo '</div>';
	}

	?>
		<div class="wrap accredible-credentials-wrap">
			<h2>Accredible Certificates &amp; Badges</h2>
			<p>To create new digital certificates or open badges:
				<ol>
					<li>Select one or more users in the table</li>
					<li>Select a group from the drop-down</li>
					<li>Click Create Credentials</li>
				</ol>
			</p>
			<p>To update certificate or badge appearance or to create a new group visit: <a href="https://dashboard.accredible.com/issuer/dashboard/" target="_blank">https://dashboard.accredible.com/issuer/dashboard/</a></p>
		<?php
		if ( 1 === get_option( 'automatically_issue_certificates' ) ) {
			echo '<p>You are automatically issuing credentials when a student completes a course. To disable this, please amend your settings.</p>';
		}
		?>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
							<?php
							wp_nonce_field( 'accredible_certificates_bulk_action', 'accredible_certificates_nonce' );
							$table_instance->prepare_items();
							$table_instance->search_box( 'Search Users', 'search' );
							$table_instance->display();
							?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
		</div>

	<?php

}

?>
