<?php
class SermonManagerImport
{

    /**
     * Location of folder containing mp3s, sermons, or files
     * Default is sermon-manager-import
     */
    protected $folder_name = '';

    protected $uploads_details = array();

    /**
     * Path to the folder containing mp3s, sermons, or files
     *
     */
    protected $folder_path = "";

    /**
     * Base URL path
     *
     */
    protected $base_path = "";

    /**
     * Messages to be displayed
     * @var array
     *
     * Two dimensions
     * [numeric index]
     * |--[message @string]
     * |--[error @booleans]
     *
     */
    protected $messages = array();

    /*--------------------------------------------*
     * Constructor
     *--------------------------------------------*/

    /**
     * Initializes the plugin by setting localization, filters, and administration functions.
     */
    public function __construct()
    {
        // Load plugin text domain
        add_action( 'init', array( $this, 'plugin_textdomain' ) );

        // Register admin styles and scripts
        add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

        // Add Sermon Manager Import to the posts page
        add_action( 'admin_menu', array( $this, 'action_add_menu_page' ) );

        // Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        // register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        // register_uninstall_hook( __FILE__, array( $this, 'uninstall' ) );

        /*
         *
         * The second parameter is the function name located within this class. See the stubs
         * later in the file.
         *
         * For more information:
         * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
         */

        include_once 'getid3/getid3.php';

        self::set_upload_details();
        self::set_folder_path();
        self::set_base_path();

        // Customizes the media uploader
        add_action( 'admin_init', array( $this, 'action_replace_thickbox_text' ) );
        add_filter( 'wp_handle_upload_prefilter', array( $this, 'filter_sermon_upload_pre_upload' ) );
        add_filter( 'wp_handle_upload', array( $this, 'filter_sermon_upload_post_upload' ) );

        // Adds help menu to plugin
        add_action( 'current_screen', array( $this, 'action_add_help_menu' ) );

        // filter posts
        add_filter( 'posts_where', array( $this, 'filter_title_like_posts_where') , 10, 2 );


        // TODO Display messages NOT running at a time when messages are there
        //add_action( 'admin_notices', array( $this, 'display_notices') );

    } // end constructor

    /**
     * Fired when the plugin is activated.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function activate( $network_wide )
    {
        // TODO: Define activation functionality here

        // create folder if it doesn't already exist
        self::create_folder();

    } // end activate

    /**
     * Fired when the plugin is deactivated.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function deactivate( $network_wide )
    {
        // TODO: Define deactivation functionality here
    } // end deactivate

    /**
     * Fired when the plugin is uninstalled.
     *
     * @param boolean $network_wide True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog
     */
    public function uninstall( $network_wide )
    {
        // TODO: Define uninstall functionality here
    } // end uninstall

    /**
     * Loads the plugin text domain for translation
     */
    public function plugin_textdomain()
    {
        // TODO: replace "plugin-name-locale" with a unique value for your plugin
        $domain = 'sermon-manager-import';
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    } // end plugin_textdomain

    /**
     * Registers and enqueues admin-specific styles.
     */
    public function register_admin_styles()
    {
        // wp_enqueue_style( 'bootstrap', plugins_url( 'css/bootstrap.min.css', __FILE__ ) );
        wp_enqueue_style( 'sermon-upload-admin-styles', plugins_url( 'css/admin.css', __FILE__ ) );
        wp_enqueue_style( 'thickbox' );

    } // end register_admin_styles

    /**
     * Registers and enqueues admin-specific JavaScript.
     */
    public function register_admin_scripts()
    {
        // wp_enqueue_script( 'bootstrap', plugins_url( 'js/bootstrap.min.js', __FILE__ ) );
        wp_enqueue_script( 'sermon-upload-admin-script', plugins_url( 'js/admin.js', __FILE__ ) );
        wp_enqueue_script( 'media-upload' );
        wp_enqueue_script( 'thickbox' );

    } // end register_admin_scripts

    /*--------------------------------------------*
     * Set/Get Variables
     *---------------------------------------------*/

    /**
     * Word Press method returns an array of directions to the upload directory
     *
     */
    public function set_upload_details()
    {
        $uploads_details = wp_upload_dir();

        $this->uploads_details = $uploads_details;
    }

    /**
     * Sets the folder where the mp3 sem_get(key)files are located at
     *
     */
    public function set_folder_path()
    {
        $this->folder_path = $this->uploads_details['basedir'] . '/' . get_option('upload_folder');
    }

    /**
     * Sets the base path
     *
     */
    public function set_base_path()
    {
        $this->base_path = parse_url( $this->uploads_details['baseurl'], PHP_URL_PATH );
    }

    /**
     * Sets the messages array
     *
     * @param message as string
     * @param type as string
     * Type default is '' for a warning message (yellow)
     * Type value of 'error' results in an error message (red)
     * Type value of 'success' results in an success message (green)
     */
    public function set_message( $message, $type = '' )
    {
        $this->messages[] = array( "message" => $message, "type" => $type);
    }

    /*--------------------------------------------*
     * Core Functions
     *---------------------------------------------*/

    /**
     * Creates a folder based on the path provided
     *
     */
    public function create_folder()
    {
        // check if directory exists and makes it if it isn't
        if ( !is_dir( $this->folder_path ) ) {
            if ( !mkdir( $this->folder_path, 0777 ) ) {
                $this->set_message('Could not make the folder for you to put your files in, please check your permissions. <br />Attempted to create folder at ' . $this->folder_path, 'error');
            }
        }
    }

    /**
     * Gives an array of mp3 files to turn in to posts
     *
     * @param unknown $folder_path
     *
     * @return $array
     *  Returns an array of mp3 file names from the directory created by the plugin
     */
    public function mp3_array( $folder_path )
    {
        // scan folders for files and get id3 info
        $audio_files = array_slice( scandir( $folder_path ), 2 ); // cut out the dots..
        // filter out all the non mp3 files
        $audio_files = array_filter( $audio_files, array($this, "mp3_only") );
        // sort the files
        sort( $audio_files );

        return $audio_files;
    }

    /**
     * Takes a string and only returns it if it has '.mp3' in it.
     *
     * @internal TODO is there a better way of filtering?
     *
     * @param unknown $string
     *   A string, possibly containing .mp3
     *
     * @return
     *   Returns a string.  Only if it contains '.mp3' or it returns FALSE
     */
    public function mp3_only( $filename )
    {
        $findme = '.mp3';
        $pos = strpos( $filename, $findme );

        if ($pos !== false) {
            return $filename;
        } else {
            return FALSE;
        }
    }

    /**
     * Creates a sermon from an mp3 file.
     *
     * @internal TODO refactor function
     *
     * @param unknown $path
     *  The base path to the folder containing the audio files to convert to posts
     *
     */
    public function audio_to_post()
    {
        // get an array of mp3 files
        $audio_files = $this->mp3_array( $this->folder_path );

        // check of there are files to process
        if ( count( $audio_files ) == 0 ) {
            $this->set_message( 'There are no usable files in ' . $this->folder_path . '.' );

            return;
        }

        $post_all = isset( $_POST['create-all-posts'] );

        // loop through all the files and create posts
        if ($post_all) {
            $limit = count( $audio_files );
            $sermon_to_post = 0;
        } else {
            $sermon_file_name = $_POST['filename'];
            $sermon_to_post = array_search( $sermon_file_name, $audio_files, true );

            if ($sermon_to_post === false) {
                $this->set_message( 'Sermon could not be found in the folder of your uploads. Please check and ensure it is there.', 'error' );

                return;
            } elseif ( !is_numeric( $sermon_to_post ) ) {
                $this->set_message( 'Key in mp3 files array is not numeric for ' . $audio_files[$sermon_to_post] . '."', 'error' );

                return;
            }
            $limit = $sermon_to_post + 1;

        }
        
        for ($i=$sermon_to_post; $i < $limit; $i++) {

            // Analyze file and store returned data in $ThisFileInfo
            $file_path = $this->folder_path . '/' . $audio_files[$i];

            // TODO This may be redundent could just send via POST; security vunerablity?
            // Sending via post will not write the changes the to the file.
            // May be useful for changing/setting the publish date
            $audio = $this->get_ID3($file_path);

            //ID3 tag mapping options
            $options = get_option( 'smi_options' );

            if($options['date'] === '')
                $date = $this->dates($audio_files[$i]);
            else
                $date = $this->dates($audio[$options['date']]);

            // check if we have a title
            if ($audio[$options['sermon_title']]) {

                // check if post exists by search for one with the same title
                $search_args = array(
                    'post_title_like' => $audio[$options['sermon_title']]
                );
                $title_search_result = new WP_Query( $search_args );

                // If there are no posts with the title of the mp3 then make the post
                if ($title_search_result->post_count == 0) {

                    // create basic post with info from ID3 details
                    $my_post = array(
                        'post_title'  => $audio[$options['sermon_title']],
                        'post_name'   => $audio[$options['sermon_title']],
                        'post_date'   => $date['file_date'],
                        'post_status' => $options['publish_status'],
                        'post_type'   => 'wpfc_sermon',
                        'tax_input'   => array (
                                            'wpfc_preacher'      => $audio[$options['preacher']],
                                            'wpfc_sermon_series' => ($options['bible_book_series']) ? $this->get_bible_book($audio[$options['bible_passage']]) : $audio[$options['sermon_series']],
                                            'wpfc_sermon_topics' => $audio[$options['sermon_topics']],
                                            'wpfc_bible_book'    => $this->get_bible_book($audio[$options['bible_passage']]),
                                            'wpfc_service_type'  => $this->get_service_type($date['meridiem']),
                            )
                    );

                    // Insert the post!!
                    $post_id = wp_insert_post( $my_post );

                    // move the file to the right month/date directory in wordpress
                    $wp_file_info = wp_upload_bits( basename( $file_path ), null, file_get_contents( $file_path ) );

                    /**
                    * @internal Delete unattached entry in the media library
                    * @internal Searches for a post in the wp_posts table that is an attachment type with an inherited status and matches the search terms
                    * @internal Trys to find by ID3 Title as WP 3.6 gets it from the file
                    * @internal If more than one or none is found try searching using the file name instead.
                    *
                    * @internal Important that this occur after the file is moved else the file is also deleted.
                    */

                    $args = array(
                        'post_type' => 'attachment',
                        'post_status' => 'inherit',
                        's' => $audio[$options['sermon_title']],
                        );
                    $query = new WP_Query( $args );

                    if ($query->found_posts == 1) {
                        wp_delete_attachment( $query->post->ID, $force_delete = false );
                    } else {
                        $filename = pathinfo($audio_files[$i],PATHINFO_FILENAME);

                        $args = array(
                        'post_type' => 'attachment',
                        'post_status' => 'inherit',
                        's' => $filename,
                        );
                        $query = new WP_Query( $args );

                        if ($query->found_posts == 1) {
                            wp_delete_attachment( $query->post->ID, $force_delete = false );
                        } else {
                           $this->set_message( 'No previous attachment deleted. You may have an unattached media entry in the media library. Or you may have uploaded files to the server via another method.', 'warning' );
                        }
                    }

                    // add the file to the sermon/post as an attachment in the media library
                    $wp_filetype = wp_check_filetype( basename( $wp_file_info['file'] ), null );
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => $audio[$options['sermon_title']],
                        'post_content'   => $audio[$options['sermon_title']].' by '.$audio[$options['preacher']].' from '.$audio[$options['sermon_series']].'. Released: '.$audio['year'],
                        'post_status'    => 'inherit',
                        'guid'           => $wp_file_info['file'],
                        'post_parent'    => $post_id,
                    );
                    $attach_id = wp_insert_attachment( $attachment, $wp_file_info['file'], $post_id );
                    wp_update_attachment_metadata( $post_id, $attachment );

                    // if moved correctly delete the original
                    if ( empty( $wp_file_info['error'] ) ) {
                        unlink( $file_path );
                    }

                    // This is for embeded images
                    // you must first include the image.php file
                    // for the function wp_generate_attachment_metadata() to work
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $wp_file_info['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    add_post_meta( $post_id, 'sermon_date', $date['unix_date'], $unique = false );
                    add_post_meta( $post_id, 'bible_passage', $audio[$options['bible_passage']], $unique = false );
                    add_post_meta( $post_id, 'sermon_audio', $wp_file_info['url'], $unique = false );

                    // TODO add support for these values
                    // add_post_meta( $post_id, 'sermon_video', $meta_value, $unique = false );
                    // add_post_meta( $post_id, 'sermon_notes', $meta_value, $unique = false );
                    add_post_meta( $post_id, 'sermon_description', $audio[$options['sermon_description']], $unique = false );

                    // TODO add support for featured image

                    // TODO add option to publish sermon from import or make drafts from import
                    // $updatePost               = get_post( $post_id );
                    // $updated_post                = array();
                    // $updated_post['ID']          = $post_id;
                    // $updated_post['post_status'] = 'published';
                    // wp_update_post( $updated_post );

                    $this->set_message( 'Post created: ' . $audio[$options['sermon_title']], 'success');
                } else {
                    $this->set_message( 'Post already exists: ' . $audio[$options['sermon_title']] );
                }
            } else {
                if (!$title) {
                    $this->set_message( 'The title for the file ' . $sermon_file_name . 'was not set. This is needed to create a post with that title.', 'error' );
                }
            }
        }
    }

    /**
     * Determines the date to publish the post
     *
     * @param unknown $filename
     * String, file name
     *
     * @return array
     * Keyed array with display_date, file_date, unix_date, meridiem
     */
    public function dates( $filename )
    {
        //Find date function
        require_once plugin_dir_path( __FILE__ ) . 'function.find_date.php';

        $file_date = find_date( $filename );

        if($file_date) {
$display_date    = date( 'F j, Y', strtotime($file_date['year'] . '-' . $file_date['month'] . '-' . $file_date['day'] . ' ' . '06:00:00'));
$publish_date = $file_date['year'] . '-' . $file_date['month'] . '-' . $file_date['day'] . ' ' . '06:00:00';
$unix_date    = strtotime($publish_date);
$meridiem     = $file_date['meridiem'];

        //Get the date from the file name minus the extention
        // $file_length = strlen( pathinfo($filename, PATHINFO_FILENAME) );

        // if ($file_length >= 8 && is_numeric($file_length)) {
        //     $file_date = substr( $filename, 0, 8 );

        //     // Set publish_date for word press post
        //     // Set unix_date for other plugins and as a common date
        //     if ( is_numeric( $file_date ) ) {
        //         $file_year    = substr( $file_date, 0, 4 );
        //         $file_month   = substr( $file_date, 4, 2 );
        //         $file_days    = substr( $file_date, 6, 2 );
        //         $file_date    = $file_year . '-' . $file_month . '-' . $file_days . ' ' . '06:00:00';
        //         $publish_date = $file_date;
        //         $unix_date    = strtotime($publish_date);
        //         // Set meridiem
        //         $file_meridiem = pathinfo($filename, PATHINFO_FILENAME);
        //         if ( preg_match("/(a|A)$|(am|AM)$|morning/", $file_meridiem) )
        //             $meridiem = 'am';
        //         elseif ( preg_match("/(p|P)$|(pm|PM)$|evening/", $file_meridiem) )
        //             $meridiem = 'pm';
        //         else
        //             $meridiem = '';
        //     } else {
        //         // No date could be determined from the file name
        //         // Set publish_date, unix_date, and meridiem to the current time
        //         $publish_date = date( 'Y-m-d', time() );
        //         $unix_date    = date( 'U', time() );
        //         $meridiem     = date( 'a', time() );
        //         // Set file_date to the current time to determine the display_date below
        //         $file_date    = time();
        //     }

        //     // Set display_date for admin page and modal
        //     $file_time = strtotime( $file_date );

        //     if ($file_time) {
        //         $display_date = date( 'F j, Y', $file_time );
        //     } else {
        //         // No date could be determined from the file name
        //         // Set display_date to the current time
        //         $display_date = date( 'F j, Y', time()) ;
        //         $this->set_message( 'The publish date for ' . $filename . ' could not be determined. It will be published ' . $display_date . ' if you do not change it.' );
        //     }
        } else {
            // No date could be determined from the file name
            // Sets all dates to the current time
            $display_date = date( 'F j, Y', time() );
            $publish_date = date( 'Y-m-d', time() );
            $unix_date    = date( 'U', time() );
            $meridiem     = date( 'a', time() );
            $this->set_message( 'The publish date for ' . $filename . ' could not be determined. It will be published ' . $display_date . ' if you do not change it.' );
        }

        $return_array = array(
            'display_date' => $display_date,
            'file_date'    => $publish_date,
            'unix_date'    => $unix_date,
            'meridiem'     => $meridiem,
            );
var_dump($return_array);
        return $return_array;
    }

    /**
     * Gets the ID3 info of a file
     *
     * @param unknown $file_path
     * String, base path to the mp3 file
     *
     * @return array
     * Keyed array with title, comment and category as keys.
     */
    public function get_ID3( $file_path )
    {
        // Initialize getID3 engine
        $get_ID3 = new getID3;
        $ThisFileInfo = $get_ID3->analyze( $file_path );

        $imageWidth = "";
        $imageHeight = "";
        /**
         * Optional: copies data from all subarrays of [tags] into [comments] so
         * metadata is all available in one location for all tag formats
         * meta information is always available under [tags] even if this is not called
         */
        getid3_lib::CopyTagsToComments( $ThisFileInfo );

        $tags = array('title' => sanitize_text_field( $ThisFileInfo['filename'] ), 'genre' => '', 'artist' => '', 'album' => '', 'year' => '');

        foreach ($tags as $key => $tag) {
            if ( array_key_exists($key, $ThisFileInfo['tags']['id3v2']) ) {
                $value = sanitize_text_field( $ThisFileInfo['tags']['id3v2'][$key][0] );
                $tags[$key] = $value;
            }
        }

        if ( isset($ThisFileInfo['comments_html']['comment']) ) {
            $value = sanitize_text_field( $ThisFileInfo['comments_html']['comment'][0] );
            $tags['comment'] = $value;
        }

        $tags['bitrate'] = sanitize_text_field( $ThisFileInfo['bitrate'] );
        $tags['length'] = sanitize_text_field( $ThisFileInfo['playtime_string'] );

        if ( isset($ThisFileInfo['comments']['picture'][0]) ) {
            $pictureData = $ThisFileInfo['comments']['picture'][0];
            $imageinfo = array();
            //$imagechunkcheck = getid3_lib::GetDataImageSize($pictureData['data'], $imageinfo);
            $imageWidth = "150"; //$imagechunkcheck[0];
            $imageHeight = "150"; //$imagechunkcheck[1];
            $tags['image'] = '<img src="data:'.$pictureData['image_mime'].';base64,'.base64_encode($pictureData['data']).'" width="'.$imageWidth.'" height="'.$imageHeight.'" class="img-polaroid">';
        }

        return $tags;
    }

    /**
     * Display the sermon upload page
     *
     */
    public function display_plugin_page()
    {
        // Posts the audio files
        if ( isset( $_POST ) ) {
            if ( isset($_POST['post']) || isset($_POST['create-all-posts']) ) {
                $this->audio_to_post();
            } elseif ( isset($_POST['filename']) ) {
                $this->write_tags();
            }
        }

        $audio_files = $this->mp3_array( $this->folder_path );
        $audio_details = "";

        // list files and details
        foreach ($audio_files as $file) {
            $file_path       = $this->folder_path.'/'.$file;
            $id3Details     = $this->get_ID3( $file_path );
            $date           = $this->dates( $file );
            $audio_details .= $this->display_file_details( $id3Details, $file, $date['display_date'] );
        }

        self::display_notices();

        require_once 'views/admin.php';
    }

    /**
     * Displays administrative warnings and errors
     * DOES NOT use the 'admin_notices' action
     *
     */
    public function display_notices()
    {
        $message_count = count( $this->messages );
        $i = 0;
        while ($i < $message_count) {
            $type = ($this->messages[$i]['type'] == '') ? '' : $this->messages[$i]['type'];
            echo '<div class="' . $type . '">' . $this->messages[$i]['message'] . '</div>';
            $i++;
        }
    }

    /**
     * Display file details
     *
     * @param array $id3Details
     * Array generated by get_ID3()
     *
     * @param string $file
     * File name
     *
     * @param string $display_date
     * Date of the file taken from the file name
     *
     * @return string
     * Returns a string formated for display
     *
     */
    public function display_file_details( $id3Details, $file, $display_date )
    {
        $displayTitle    = empty($id3Details['title']) ? $file : $id3Details['title'];
        $displaySpeaker  = empty($id3Details['artist']) ? '&nbsp;' : $id3Details['artist'];
        $displayText     = empty($id3Details['comment']) ? '&nbsp;' : $id3Details['comment'];
        $displayCategory = empty($id3Details['genre']) ? '&nbsp;' : $id3Details['genre'];
        $displayAlbum    = empty($id3Details['album']) ? '&nbsp;' : $id3Details['album'];
        $displayYear     = empty($id3Details['year']) ? '&nbsp;' : $id3Details['year'];
        $displayLength   = empty($id3Details['length']) ? '&nbsp;' : $id3Details['length'];
        $displayBitrate  = empty($id3Details['bitrate']) ? '&nbsp;' : $id3Details['bitrate'];
        $displayImage    = empty($id3Details['image']) ? 'No image embded' : $id3Details['image'];
        $fileUnique      = str_replace('.', '_', str_replace(' ', '_', $file));

        $info = '<li class="sermon_dl_item">
            <form method="post" action="">
                <input type="submit" class="button-primary" name="'. $file . '" value="' . __('Import') . '" />
                <input type="hidden" name="filename" value="' . $file . '">
                <input type="hidden" name="post" value="Post">
                <button type="button" id="details-' . $fileUnique . '" class="button-secondary">' . __('Details') . '</button>
            <span><b>' . $displayTitle . '</b></span>
            </form>
            <dl id="dl-details-' . $fileUnique . '" class="dl-horizontal">
                <dt>Speaker:      </dt><dd>&nbsp;' . $displaySpeaker . '</dd>
                <dt>Bible Text:   </dt><dd>&nbsp;' . $displayText . '</dd>
                <dt>Publish Date: </dt><dd>&nbsp;' . $display_date .'</dd>
                <dt>Category:     </dt><dd>&nbsp;' . $displayCategory . '</dd>
                <dt>Album:        </dt><dd>&nbsp;' . $displayAlbum . '</dd>
                <dt>Year:         </dt><dd>&nbsp;' . $displayYear . '</dd>
                <dt>Length:       </dt><dd>&nbsp;' . $displayLength . '</dd>
                <dt>Bitrate:      </dt><dd>&nbsp;' . $displayBitrate . '</dd>
                <dt>File name:    </dt><dd>&nbsp;' . $file . '</dd>
                <dt>Picture:      </dt><dd>&nbsp;' . $displayImage . '</dd>
            </dl>
        </li>';

        return $info;
    }

    /**
     * Returns the bible book from a well formated bible reference
     *
     * @param string $text
     * Well formated bible reference (e.g. John 1, John 1:11, 1 John 5:1-3, 3 Jh 5 )
     *
     * @return string
     * @author khornberg
     **/
    public function get_bible_book( $text )
    {
        preg_match('/(^\w{1,3}\s)?\w+/', $text, $matches);

        return $matches[0];
    }

    /**
     * Returns the service type based on meridiem
     *
     * @internal One time use only. Fits only a specific situation. For my local church.
     *
     * @param string text
     *
     * @return string
     * @author khornberg
     **/
    public function get_service_type( $meridiem )
    {
        if ( $meridiem === 'pm' )
            return 'Sunday Evening';
        else
            return 'Sunday Morning';
    }

    /**
     * Display admin notice using the admin_notices action
     *
     * @return void
     * @author khornberg
     **/
    public function activate_notice()
    {
        echo '<div class="error"><p>You much have <a href="http://www.wpforchurch.com/products/sermon-manager-for-wordpress/">Sermon Manager for Wordpress</a> installed and activated.</p></div>';
    }

    /**
     * NOTE:  Actions are points in the execution of a page or process
     *        lifecycle that WordPress fires.
     *
     *    WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
     *    Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
     *
     */

    public function action_replace_thickbox_text()
    {
        global $pagenow;
        if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow) {
            // Now we'll replace the 'Insert into Post Button' inside Thickbox
            add_filter( 'gettext', array( $this, 'filter_replace_thickbox_text' ), 1, 3 );
        }
    }

    /**
    * Adds help menus items for Sermon upload
    **/
    public function action_add_help_menu()
    {
        $sermon_help_upload = '<p>' . __( 'Upload a sermon by clicking the "Upload Sermon" button. To finish the upload, in the media upload box, click "Upload Sermon" or close the dialog box.' ) . '</p>' .
            '<p>' . __( 'The sermons will appear in the sermon list area below this help area.') . '</p>' .
            '<p>' . __( 'Click the "Import" button to post the individual sermon.' ) . '</p>'.
            '<p>' . __( 'Click the "Details" button view the details (ID3 information) about the individual sermon.' ) . '</p>'.
            '<p>' . __( 'Click the "Import all Sermons" button to attempt to post all sermons. <br /> Depending on your server configuration, your server may stop processing before all the sermons are imported. In this case, click "Import all sermons" again until all sermons are imported.' ) . '</p>';

            $sermon_help_details = '<p>' . __( 'Files are uploaded to ' ) . $this->folder_path . ' and moved on posting to'. $this->base_path . '.</p>' .
            '<p>' . __( 'This plugin only searchs for mp3 files. By changing the function mp3_only in sermon-manager-import.php, one can search for other file types or modify the mp3_array function.' ) . '</p>';

        get_current_screen()->add_help_tab( array(
                'id'      => 'sermon2',
                'title'   => __( 'Uploading Sermons' ),
                'content' => $sermon_help_upload,
            )
        );

        get_current_screen()->add_help_tab( array(
                'id'      => 'sermon3',
                'title'   => __( 'Technical Details' ),
                'content' => $sermon_help_details,
            )
        );
    }

    /**
     * Adds Sermon Manager Import page to Sermons page on the admin menu
     *
     */
    public function action_add_menu_page()
    {
        // include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        // if ( is_plugin_active( "sermon-manager-for-wordpress/sermons.php" )) {
            // Create the menu item for users with "upload_files" ability
            add_submenu_page( 'edit.php?post_type=wpfc_sermon', 'Import Sermons', 'Import Sermons', 'upload_files', 'sermon-manager-import', array ($this, 'display_plugin_page') );
        // }
        // else {
        //     add_action( '_admin_notices', 'activate_notice' );
        // }
    }

    /**
     * NOTE:  Filters are points of execution in which WordPress modifies data
     *        before saving it or sending it to the browser.
     *
     *    WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
     *    Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
     *
     */

    public function filter_replace_thickbox_text( $translated_text, $text, $domain )
    {
        if ('Insert into Post' == $text) {
            $referer = strpos( wp_get_referer(), 'sermon-upload' );
            if ($referer != '') {
                return __( 'Upload Sermon' );
            }
        }

        return $translated_text;
    }

    /**
    * Changes the location of uploads
    **/

    public function filter_sermon_upload_pre_upload( $file )
    {
        add_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );

        return $file;
    }

    public function filter_sermon_upload_post_upload( $fileinfo )
    {
        remove_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );

        return $fileinfo;
    }

    public function sermon_upload_custom_upload_dir( $path )
    {
        if ( !empty( $path['error'] ) ) { return $path; } //error; do nothing.
        $customdir      = $this->folder_name;
        $path['path']   = str_replace( $path['subdir'], '', $path['path'] ); //remove default subdir (year/month)
        $path['url']    = str_replace( $path['subdir'], '', $path['url'] );
        $path['subdir'] = $customdir;
        $path['path']  .= $customdir;
        $path['url']   .= $customdir;
        if ( !wp_mkdir_p( $path['path'] ) ) {
            return array( 'error' => sprintf( __( 'Unable to create directory %s. Is the parent directory writable by the server?' ), $path['path'] ) );
        }

        return $path;
    }

    /**
     * Adds a select query that lets you search for titles more easily using WP Query
     */
    public function filter_title_like_posts_where( $where, &$wp_query )
    {
        global $wpdb;
        if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
            $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'' .
                esc_sql( like_escape( $post_title_like ) ) . '%\'';
        }

        return $where;
    }

} // end class


//TODO add user level check
$sermon_manager_import = new SermonManagerImport();

//sdg
