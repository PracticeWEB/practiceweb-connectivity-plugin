<div>
  <h2>PracticeWEB connectivity</h2>
  Options relating to the PracticeWEB Connectivity Plugin.
  <form action="options.php" method="post">
    <?php settings_fields('practiceweb-connectivity-group'); ?>
    <label for="practiceweb-connectivity-config[apiKey]">PracticeWEB API Key</label>
    <input type="text" name="practiceweb-connectivity-config[apiKey]" value="<?php print esc_attr($vars['practiceweb-connectivity-config']['apiKey'])?>" />
      <fieldset>
          <legend>Services</legend>
          <label>News <input type="checkbox" name="practiceweb-connectivity-config[service][news]" value="news" <?php echo (isset($vars['checked']['news']) ? $vars['checked']['news'] : '') ?>></label>
          <label>Deadlines <input type="checkbox" name="practiceweb-connectivity-config[service][deadlines]" value="deadlines" <?php echo (isset($vars['checked']['deadlines']) ? $vars['checked']['deadlines'] : '') ?>></label>
      </fieldset>

    <?php submit_button() ?>
  </form>
</div>
