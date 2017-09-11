<div>
  <h2>PracticeWEB connectivity</h2>
  Options relating to the PracticeWEB Connectivity Plugin.
  <form action="options.php" method="post">
    <?php settings_fields('practiceweb-connectivity-group'); ?>
    <label for="practiceweb-connectivity-config[apiKey]">PracticeWEB API Key</label>
    <input type="text" name="practiceweb-connectivity-config[apiKey]" value="<?php print esc_attr($vars['practiceweb-connectivity-config']['apiKey'])?>" />

    <?php submit_button() ?>
  </form>
</div>
