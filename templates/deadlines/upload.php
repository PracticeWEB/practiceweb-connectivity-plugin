<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"  enctype="multipart/form-data">
    <?php wp_nonce_field('practiceweb_connectivity_deadlines_upload') ?>
    <fieldset>
        <legend>Upload deadlines</legend>
        <label>File: <input type="file" name="deadlinesfile"></label>
    </fieldset>
    <input type="hidden" name="action" value="practiceweb_connectivity_deadlines_upload">
    <?php submit_button(); ?>
</form>
