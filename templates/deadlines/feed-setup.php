<h2>Setup your Practiceweb News Feed</h2>
<?php if ($vars['noKey']) { ?>
<p>
    You don't appear to have added your api Key. Your feed will not work until this is added.
    TODO link back.
</p>
<?php } ?>

<?php if ($vars['exists']) { ?>
<p>
    You already appear to have a working feed. You can still use this form to update the feed location.
</p>
<?php } ?>
<form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('practiceweb_connectivity_deadlines_setup') ?>
    <fieldset>
        <legend>Feed Details</legend>
        <label>URL: <input type="text" name="uri" value="<?php print esc_attr($vars['uri']); ?>"></label>
        <label>Fetch as soon as possible: <input type="checkbox" name="fetch" value="yes" checked></label>
    </fieldset>
    <input type="hidden" name="action" value="practiceweb_connectivity_deadlines_setup">
    <?php submit_button(); ?>
</form>
