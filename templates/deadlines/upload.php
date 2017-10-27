<h2>Setup your Practiceweb Deadlines Content</h2>
<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"  enctype="multipart/form-data">
    <?php wp_nonce_field('practiceweb_connectivity_deadlines_upload') ?>

    <h3>Choose method:</h3>
    <div>

    <fieldset>
        <label for="loadMethod-upload">Upload deadlines</label>
        <input type="radio" name="loadMethod" value="upload" id="loadMethod-upload">
        <div class="radio-reveal">
            <label for="deadlinesfile">File: </label><input type="file" name="deadlinesfile">
        </div>
    </fieldset>

    <fieldset>
        <label for="loadMethod-download">Download deadlines</label>
        <input type="radio" name="loadMethod" value="download" id="loadMethod-download">
        <div class="radio-reveal">
            <label for="url">Url: </label><input type="text" name="url">
        </div>
    </fieldset>
    <input type="hidden" name="action" value="practiceweb_connectivity_deadlines_upload">
    <?php submit_button(); ?>
</form>
