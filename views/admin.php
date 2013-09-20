<div class="wrap">
    <h2>Sermon Upload</h2>

    <p><?php _e('For help using this plugin, click the help menu in the upper right hand corner.'); ?></p>

    <input id="sermon_upload_button" type="button" value="Upload Sermon(s)" class="button" />
    <br />
    <br />


      <?php
        if($audio_details !== "") {
      ?>
        <form method="post" action="">
          <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Import all sermons') ?>" />
        </form>
          <h4><?php _e('Sermons are listed by file name and shown with the sermon title.'); ?></h4>
      <?php
        } else {
      ?>
          <p class="well well-small">No sermons to post.</p>
      <?php
       var_dump($this->folder_path);
        }
      ?>
      <ul class="unstyled">
        <?php echo $audio_details; ?>
      </ul>
  </div>
