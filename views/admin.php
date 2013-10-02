<div class="wrap">
    <h2>Sermon Upload</h2>

    <p><?php _e('For help using this plugin, click the help menu in the upper right hand corner.'); ?></p>

    <input id="sermon_upload_button" type="button" value="Upload Sermon(s)" class="button" />
    <br />
    <br />

      <?php
        if ( isset( $audio_details ) && $audio_details != null) {
      ?>
        <form method="post" action="<?php the_permalink(); ?>">
          <input type="submit" class="button-primary" name="create-all-posts" value="<?php _e('Import all sermons') ?>" />
        </form>
          <h4><?php _e('Sermons are listed by file name and shown with the sermon title.'); ?></h4>
      <?php
        } else {
      ?>
          <p class="">No sermons to post.</p>
      <?php
        }
      ?>
      <ul class="">
        <?php if ( isset( $audio_details ) ) { echo $audio_details; } ?>
      </ul>
  </div>
