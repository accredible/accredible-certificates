<div class="wrap">
    <h2>Accredible Certificates</h2>
    <form method="post" action="options.php"> 
        <?php @settings_fields('accredible_certificates-group'); ?>

        <?php do_settings_sections('accredible_certificates'); ?>

        <?php @submit_button(); ?>
    </form>
</div>