<?php
/**
 * Plugin Name.
 *
 * @package   Sermon Manager Import
 * @author    Kyle Hornberg
 * @license   GPLv3
 * @link      https://github.com/khornberg/sermon-manager-import
 * @copyright 2013 Kyle Hornberg
 */

/**
 * Plugin class.
 *
 * @package SermonManagerImport
 * @author  Kyle Hornberg
 */
class SermonManagerImport {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   0.2.0
	 *
	 * @var     string
	 */
	const VERSION = '0.2.1';

	/**
	 * Unique identifier for your plugin.
	 *
	 * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
	 * match the Text Domain file header in the main plugin file.
	 *
	 * @since    0.2.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'sermon-manager-import';

	/**
	 * Instance of this class.
	 *
	 * @since    0.2.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    0.2.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Folder location containing sermons
	 * 
	 * @internal  Default is sermon-manager-import
	 *
	 * @since  0.1.0
	 *
	 * @var    string
	 */
	protected $folder_path = null;

	/**
	 * Base URL path
	 *
	 * @since  0.1.0
	 *
	 * @var    string
	 */
	protected $base_path = null;

	/**
	 * Messages to be displayed
	 *
	 * @since 0.1.0
	 * 
	 * @var array
	 *
	 * Two dimensions
	 * [numeric index]
	 * |--[message @string]
	 * |--[error @booleans]
	 *
	 */
	protected $messages = array();

	/**
	 * Sermon manager import options
	 * 
	 * @since  0.2.0
	 * 
	 * @var array
	 */
	protected $options = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     0.2.0
	 */
	private function __construct() {
		$this->options = get_option('smi_options');

		// Set paths to folders
		self::set_paths();
		self::create_folder();

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Activate plugin when new blog is added
		add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		$plugin_basename = plugin_basename( plugin_dir_path( __FILE__ ) . 'sermon-manager-import.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Load public-facing style sheet and JavaScript.
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );



		// Define custom functionality. Read more about actions and filters: http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		add_action( 'admin_init', array( $this, 'action_replace_thickbox_text' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'filter_sermon_upload_pre_upload' ) );
		add_filter( 'wp_handle_upload', array( $this, 'filter_sermon_upload_post_upload' ) );

		// Filter posts
		add_filter( 'posts_where', array( $this, 'filter_title_like_posts_where') , 10, 2 );

		// Add help menu to plugin
		add_action( 'current_screen', array( $this, 'action_add_help_menu' ) );

		// Activation notices
		add_action( 'admin_notices', array( $this, 'activate' ) );

		// Does not work on admin_notices. Seems to be hooking too early
		add_action( 'shutdown', array( $this, 'display_messages' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.2.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    0.2.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide  ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_activate();
				}
				restore_current_blog();
			} else {
				self::single_activate();
			}
		} else {
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    0.2.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( $network_wide ) {
				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}
				restore_current_blog();
			} else {
				self::single_deactivate();
			}
		} else {
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    0.2.0
	 *
	 * @param   int $blog_id ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) )
			return;

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    0.2.0
	 *
	 * @return  array|false The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";
		return $wpdb->get_col( $sql );
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    0.2.0
	 */
	private static function single_activate() {
		$html = '';

		if ( ! class_exists( 'getID3' ) ) {
			if (file_exists( ABSPATH . WPINC . '/ID3/getid3.php') ) {   
				require( ABSPATH . WPINC . '/ID3/getid3.php' );
			}
			else {
				require_once 'assets/getid3/getid3.php';
			}
		}

		if ( ! is_plugin_active( 'sermon-manager-for-wordpress/sermons.php' ) ) {
			$html .= '<div class="updated"><p><a href="http://www.wpforchurch.com/products/sermon-manager-for-wordpress/">Sermon Manager for Wordpress</a> must be activated for Sermon Manager Import plugin to work.</p></div>';
			echo $html;
		}
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    0.2.0
	 */
	private static function single_deactivate() {
		// TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.2.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     0.2.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), self::VERSION );
			wp_enqueue_style( 'thickbox' ); 
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     0.2.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery' ), self::VERSION );
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    0.2.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/public.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueues public-facing JavaScript files.
	 *
	 * @since    0.2.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_slug . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), self::VERSION );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    0.2.0
	 */
	public function add_plugin_admin_menu() {
		
		if ( ! is_plugin_active( 'sermon-manager-for-wordpress/sermons.php' ) ) {
			$this->plugin_screen_hook_suffix = add_plugins_page(
				__( 'Sermon Manager Import', $this->plugin_slug ),
				__( 'Import Sermons', $this->plugin_slug ),
				'upload_files',
				$this->plugin_slug,
				array( $this, 'display_plugin_admin_page' )
			);
		}
		else {
			$this->plugin_screen_hook_suffix = add_submenu_page( 
				'edit.php?post_type=wpfc_sermon', 
				__( 'Import Sermons', $this->plugin_slug ),
				__( 'Import Sermons', $this->plugin_slug ),
				'upload_files', 
				$this->plugin_slug, 
				array( $this, 'display_plugin_admin_page') 
			);
		}
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    0.2.0
	 */
	public function display_plugin_admin_page() {
		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			if ( isset( $_POST ) ) {
				if ( isset( $_POST['post'] ) || isset( $_POST['create-all-posts'] ) && is_plugin_active( 'sermon-manager-for-wordpress/sermons.php' ) ) {
					$this->import();
				}
			}

			$audio_files = $this->get_audio_files( $this->folder_path );
			$audio_details = null;

			// list files and details
			foreach ($audio_files as $file_name) {
				$file_path      = $this->folder_path.'/'.$file_name;
				$id3            = $this->get_ID3( $file_path );
				$date_string    = ($this->options['date'] === '') ? $file_name : $id3[$this->options['date']] ;
				$date           = $this->get_dates( $date_string, $file_name );
				$audio_details .= $this->display_file_details( $id3, $file_name, $date );
			}

			include_once( 'views/admin.php' );
		}
	}

		/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    0.2.0
	 */
	public function add_action_links( $links ) {

		if ( ! is_plugin_active( 'sermon-manager-for-wordpress/sermons.php' ) ) {
			return array_merge(
				array(
					'settings' => '<a href="' . admin_url( 'plugins.php?page=' . $this->plugin_slug .'-options' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
				),
				$links
			);
		}
		else {  
			return array_merge(
				array(
					'settings' => '<a href="' . admin_url( 'edit.php?post_type=wpfc_sermon&page=' . $this->plugin_slug .'-options' ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
				),
				$links
			);
		}

	}

	/*--------------------------------------------*
	* Sermon Manger Import Functions
	*---------------------------------------------*/

	/**
	 * Display file details
	 *
	 * @param array $id3
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
	public function display_file_details( $id3, $file, $date )
	{
		$displayImport  = ($this->options['publish_status'] === 'draft') ? __('Import as draft') : __('Import');

		$displaySermonTitle       = empty($id3[$this->options['sermon_title']]) ? '&nbsp;' : $id3[$this->options['sermon_title']];
		$displayPreacher          = empty($id3[$this->options['preacher']]) ? '&nbsp;' : $id3[$this->options['preacher']];
		$displaySermonSeries      = ( isset($this->options['bible_book_series']) ) ? $this->get_bible_book($id3[$this->options['bible_passage']]) : $id3[$this->options['sermon_series']];
		$displaySermonTopics      = empty($id3[$this->options['sermon_topics']]) ? '&nbsp;' : $id3[$this->options['sermon_topics']];
		$displaySermonDescription = empty($id3[$this->options['sermon_description']]) ? '&nbsp;' : $id3[$this->options['sermon_description']];
		$displayBiblePassage      = empty($id3[$this->options['bible_passage']]) ? '&nbsp;' : $id3[$this->options['bible_passage']];
		$displayBibleBook         = $this->get_bible_book($id3[$this->options['bible_passage']]);
		$displayService           = $this->get_service_type($date['meridiem']);

		$displayTitle   = empty($id3['title']) ? $file : $id3['title'];
		$displayArtist  = empty($id3['artist']) ? '&nbsp;' : $id3['artist'];
		$displayComment = empty($id3['comment']) ? '&nbsp;' : $id3['comment'];
		$displayGenre   = empty($id3['genre']) ? '&nbsp;' : $id3['genre'];
		$displayAlbum   = empty($id3['album']) ? '&nbsp;' : $id3['album'];
		$displayYear    = empty($id3['year']) ? '&nbsp;' : $id3['year'];
		$displayLength  = empty($id3['length']) ? '&nbsp;' : $id3['length'];
		$displayBitrate = empty($id3['bitrate']) ? '&nbsp;' : $id3['bitrate'];
		$displayImage   = empty($id3['image']) ? 'No image embded' : $id3['image'];
		$fileUnique     = str_replace('.', '_', str_replace(' ', '_', $file));

		$info = '<li class="sermon_dl_item">
			<form method="post" action="">
				<input type="submit" class="button-primary" name="'. $file . '" value="' . $displayImport . '" />
				<input type="hidden" name="filename" value="' . $file . '">
				<input type="hidden" name="post" value="Post">
				<button type="button" id="details-' . $fileUnique . '" class="button-secondary">' . __('Details') . '</button>
			<span><b>' . $displayTitle . '</b></span>
			</form>
			<dl id="dl-details-' . $fileUnique . '" class="dl-horizontal">
				<h2>Import as:</h2>
				<dt>Sermon Title:      </dt><dd>&nbsp;' . $displaySermonTitle . '</dd>
				<dt>Preacher:          </dt><dd>&nbsp;' . $displayPreacher . '</dd>
				<dt>Sermon Series:     </dt><dd>&nbsp;' . $displaySermonSeries . '</dd>
				<dt>Sermon Topics:     </dt><dd>&nbsp;' . $displaySermonTopics . '</dd>
				<dt>Sermon Descrption: </dt><dd>&nbsp;' . $displaySermonDescription . '</dd>
				<dt>Bible Passage:     </dt><dd>&nbsp;' . $displayBiblePassage . '</dd>
				<dt>Bible Book:        </dt><dd>&nbsp;' . $displayBibleBook . '</dd>
				<dt>Service Type:      </dt><dd>&nbsp;' . $displayService . '</dd>
				<dt>Publish Date: </dt><dd>&nbsp;' . $date['display_date'] .'</dd>

				<h2>On the file:</h2>
				<dt>Artist:       </dt><dd>&nbsp;' . $displayArtist . '</dd>
				<dt>Comment:      </dt><dd>&nbsp;' . $displayComment . '</dd>
				<dt>Genre:        </dt><dd>&nbsp;' . $displayGenre . '</dd>
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
	 * Gives an array of mp3 files to turn in to posts
	 *
	 * @param string $folder_path
	 *
	 * @return $array
	 *  Returns an array of mp3 file names from the directory created by the plugin
	 */
	public function get_audio_files( $folder_path )
	{
		// scan folders for files and get id3 info
		$audio_files = array_slice( scandir( $folder_path ), 2 ); // cut out the dots..
		// filter out all the non mp3 files
		$audio_files = array_filter( $audio_files, array($this, 'filter_audio_files') );
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
	public function filter_audio_files( $filename )
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
	 * Determines which files to import
	 * @return Only on errors to log messages
	 */
	public function import()
	{
		// get an array of mp3 files
		$audio_files = $this->get_audio_files( $this->folder_path );

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
		
		// Import the sermon
		for ($i=$sermon_to_post; $i < $limit; $i++) {
			$this->import_sermon($audio_files[$i]);
		}
	}

	/**
	 * Creates a sermon from an mp3 file.
	 *
	 * @param unknown $file_name
	 * The base path to the folder containing the audio files to convert to sermons
	 *
	 */
	public function import_sermon($file_name)
	{
		// Analyze file and store returned data in $ThisFileInfo
		$file_path = $this->folder_path . '/' . $file_name;

		$audio = $this->get_ID3($file_path);

		if($this->options['date'] === '')
			$date = $this->get_dates($file_name, $file_name);
		else
			$date = $this->get_dates($audio[$this->options['date']]);

		// check if we have a title
		if ($audio[$this->options['sermon_title']]) {

			// check if post exists by search for one with the same title
			$search_args = array(
				'post_title_like' => $audio[$this->options['sermon_title']]
			);
			$title_search_result = new WP_Query( $search_args );

			// If there are no posts with the title of the sermon then make the sermon
			if ($title_search_result->post_count == 0) {

				$tax_input_array = array ();

				$wpfc_options = get_option('wpfc_options');

				if ($wpfc_options['version'] < '1.8') {
					$tax_input_array = array (
						'wpfc_preacher'      => $audio[$this->options['preacher']],
						'wpfc_sermon_series' => ( isset($this->options['bible_book_series']) ) ? $this->get_bible_book($audio[$this->options['bible_passage']]) : $audio[$this->options['sermon_series']],
						'wpfc_sermon_topics' => $audio[$this->options['sermon_topics']],
						'wpfc_bible_book'    => $this->get_bible_book($audio[$this->options['bible_passage']]),
						'wpfc_service_type'  => $this->get_service_type($date['meridiem']),
						);
				} else {
					$tax_input_array = array (
						'wpfc_preacher'      => $audio[$this->options['preacher']],
						'wpfc_sermon_series' => ( isset($this->options['bible_book_series']) ) ? $this->get_bible_book($audio[$this->options['bible_passage']]) : $audio[$this->options['sermon_series']],
						'wpfc_sermon_topics' => $audio[$this->options['sermon_topics']],
						'wpfc_bible_book'    => $this->get_bible_book($audio[$this->options['bible_passage']]),
						'wpfc_service_type'  => $this->get_service_type($date['meridiem']),
						'wpfc_sermon_duration' => $audio['length'],
						'wpfc_sermon_size' => $audio['size'],
						);
				}

				// create basic post with info from ID3 details
				$my_post = array(
					'post_title'  => $audio[$this->options['sermon_title']],
					'post_name'   => $audio[$this->options['sermon_title']],
					'post_date'   => $date['file_date'],
					'post_status' => $this->options['publish_status'],
					'post_type'   => 'wpfc_sermon',
					'tax_input'   => $tax_input_array
				);

				// Insert the post!!
				$post_id = wp_insert_post( $my_post );

				// move the file to the right month/date directory in wordpress
				$wp_file_info = wp_upload_bits( basename( $file_path ), null, file_get_contents( $file_path ) );

				// error uploading file, abort
				if ( ! empty( $wp_file_info['error'])) {
					$this->set_message( 'Error importing: ' . $audio[$this->options['sermon_title']] . '<br />' . $wp_file_info['error'], 'error');
					return;
				}

				/**
				* @internal Delete unattached entry in the media library
				* @internal Searches for a post in the wp_posts table that is an attachment type with an inherited status and matches the search terms
				* @internal Trys to find by ID3 Title as WP 3.6 gets it from the file
				* @internal If more than one or none is found try searching using the file name instead.
				*
				* @internal Important that this occur after the file is moved else the file is also deleted.
				*/

				// Query for title
				$args = array(
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					's' => $audio[$this->options['sermon_title']],
					);
				$query = new WP_Query( $args );

				// Search
				if ($query->found_posts == 1) {
					wp_delete_attachment( $query->post->ID, $force_delete = false );
				} else {
					$filename = pathinfo($file_name,PATHINFO_FILENAME);

					// Query for file name
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
					'post_title'     => $audio[$this->options['sermon_title']],
					'post_content'   => $audio[$this->options['sermon_title']].' by '.$audio[$this->options['preacher']].' from '.$audio[$this->options['sermon_series']].'. Released: '.$audio['year'],
					'post_status'    => 'inherit',
					'guid'           => $wp_file_info['file'],
					'post_parent'    => $post_id,
				);
				$attach_id = wp_insert_attachment( $attachment, $wp_file_info['file'], $post_id );
				wp_update_attachment_metadata( $post_id, $attachment );

				// if moved correctly and file is still there delete the original
				if ( file_exists($file_name) && empty( $wp_file_info['error'] ) ) {
					unlink( $file_path );
				}

				// This is for embeded images and attached files
				// you must first include the image.php file
				// for the function wp_generate_attachment_metadata() to work
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_file_info['file'] );
				wp_update_attachment_metadata( $attach_id, $attach_data );

				add_post_meta( $post_id, 'sermon_date', $date['unix_date'], $unique = false );
				add_post_meta( $post_id, 'bible_passage', $audio[$this->options['bible_passage']], $unique = false );
				add_post_meta( $post_id, 'sermon_audio', $wp_file_info['url'], $unique = false );

				// TODO might add support for these values somehow
				// add_post_meta( $post_id, 'sermon_video', $meta_value, $unique = false );
				// add_post_meta( $post_id, 'sermon_notes', $meta_value, $unique = false );
				add_post_meta( $post_id, 'sermon_description', $audio[$this->options['sermon_description']], $unique = false );

				// TODO add support for featured image
				// add_post_meta( $post_id, '_thumbnail_id', $thumbnail_id)

				$link = ($this->options['publish_status'] === 'draft') ? 'post.php?post=' . $post_id . '&action=edit' : get_permalink( $post_id );
				$this->set_message( 'Sermon created: <a href="' . $link . '">' . $audio[$this->options['sermon_title']] . '</a>');
			} else {
				$this->set_message( 'Sermon already exists: ' . $audio[$this->options['sermon_title']] );
			}
		} else {
			if (!$title) {
				$this->set_message( 'The title for the file ' . $sermon_file_name . 'was not set. This is needed to create a sermon with that title.', 'error' );
			}
		}
	}

	/**
	 * Determines the date to publish the post
	 *
	 * @param string $date_string
	 *
	 * @param string $file_name
	 *
	 * @return array
	 * Keyed array with display_date, file_date, unix_date, meridiem
	 */
	public function get_dates( $date_string, $file_name )
	{
		//Find date function
		require_once plugin_dir_path( __FILE__ ) . '/assets/' . 'FindDate.php';

		$FindDate = new FindDate;
		$FindDate->format =  $this->options['date_format'];
		$FindDate->two_digit_year_split = $this->options['date_year_split'];

		$file_date = $FindDate->find( $date_string );

		if($file_date) {
			$display_date = date( 'F j, Y', strtotime($file_date['year'] . '-' . $file_date['month'] . '-' . $file_date['day'] . ' ' . '06:00:00'));
			$publish_date = $file_date['year'] . '-' . $file_date['month'] . '-' . $file_date['day'] . ' ' . '06:00:00';
			$unix_date    = strtotime($publish_date);
			$meridiem     = $file_date['meridiem'];
		} else {
			// No date could be determined from the file name
			// Sets all dates to the current time
			$display_date = date( 'F j, Y', time() );
			$publish_date = date( 'Y-m-d', time() );
			$unix_date    = date( 'U', time() );
			$meridiem     = date( 'a', time() );
			$this->set_message( 'The publish date could not be determined. '. $file_name . ' will have a date of ' . $display_date . ' unless you change it.' );
		}

		$return_array = array(
			'display_date' => $display_date,
			'file_date'    => $publish_date,
			'unix_date'    => $unix_date,
			'meridiem'     => $meridiem,
			);

		return $return_array;
	}

	/**
	 * Gets the ID3 data of a file
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

		// see en.wikipedia.org/wiki/ID3
		// subtitle tit3 in id3v2 and tsst in id3v4
		if (! empty( $ThisFileInfo['id3v2']['TIT3'][0]['data'] ) ) {
			$tags['subtitle'] = sanitize_text_field( $ThisFileInfo['id3v2']['TIT3'][0]['data'] );
		}
		else if ( ! empty( $ThisFileInfo['id3v2']['TSST'][0]['data'] ) ) {
			$tags['subtitle'] = sanitize_text_field( $ThisFileInfo['id3v2']['TSST'][0]['data'] );
		}
		else {
			$tags['subtitle'] = '';
		}

		$tags['composer'] = empty( $ThisFileInfo['id3v2']['TCOM'][0]['data'] ) ? '' : sanitize_text_field( $ThisFileInfo['id3v2']['TCOM'][0]['data'] );
		$tags['bitrate'] = sanitize_text_field( $ThisFileInfo['bitrate'] );
		$tags['length'] = sanitize_text_field( $ThisFileInfo['playtime_string'] );
		$tags['size'] = sanitize_text_field( $ThisFileInfo['filesize'] );

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
	 * Returns the bible book from a well formated bible reference
	 *
	 * @param string $text
	 * Well formated bible reference (e.g. John 1, John 1:11, 1 John 5:1-3, 3 Jh 5 )
	 *
	 * @since 0.1.0
	 * 
	 * @return string
	 * @author khornberg
	 **/
	public function get_bible_book( $text )
	{
		preg_match('/(^\w{1,3}\s)?\w+/', $text, $matches);

		return ( isset($matches[0]) ) ? $matches[0] : '';
	}

	/**
	 * Returns the service type based on meridiem
	 *
	 * @param string text
	 *
	 * @since 0.1.0
	 * 
	 * @return string
	 * @author khornberg
	 **/
	public function get_service_type( $meridiem )
	{
		if ( $meridiem === 'pm' )
			return $this->options['pm_service'];
		else
			return isset($this->options['am_service']) ? $this->options['am_service'] : null;
	}

	/*--------------------------------------------*
	 * Set/Get Variables
	 *---------------------------------------------*/


	/**
	 * Sets upload and base paths
	 *
	 * @since  0.2.0
	 *
	 */
	public function set_paths()
	{
		$uploads_details = wp_upload_dir();
		$this->folder_path = $uploads_details['basedir'] . '/' . $this->options['upload_folder'];
		$this->base_path = parse_url( $uploads_details['baseurl'], PHP_URL_PATH );
	}

	/**
	 * Creates a folder based on the path provided
	 *
	 * @since 0.1.0
	 * 
	 */
	public function create_folder()
	{
		// check if directory exists and makes it if it isn't
		if ( !is_dir( $this->folder_path ) ) {
			if ( !mkdir( $this->folder_path, 0777 ) ) {
				$this->set_message('<p>Could not make the folder for you to put your files in, please check your permissions. Attempted to create folder at <br /><b>' . $this->folder_path . '</b></p>', 'error');
			}
		}
	}


	/**
	 * Sets the messages array
	 *
	 * @param message as string
	 * @param type as string
	 * 
	 * Type default is '' for a warning message (yellow)
	 * Type value of 'error' results in an error message (red)
	 *
	 * @since  0.1.0
	 */
	public function set_message( $message, $type = 'updated' )
	{
		$this->messages[] = array( "message" => $message, "type" => $type);
	}

	/**
	 * Displays administrative warnings and errors
	 * 
	 * @since  0.1.0
	 */
	public function display_messages()
	{
		$message_count = count( $this->messages );
		$i = 0;
		while ($i < $message_count) {
			echo '<div class="' . $this->messages[$i]['type'] . '">' . $this->messages[$i]['message'] . '</div>';
			$i++;
		}
	}

	/**
	 * NOTE:  Actions are points in the execution of a page or process
	 *        lifecycle that WordPress fires.
	 *
	 *        WordPress Actions: http://codex.wordpress.org/Plugin_API#Actions
	 *        Action Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
	 *
	 * @since    0.2.0
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
	*
	* @since  0.1.0
	**/
	public function action_add_help_menu()
	{
		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			$sermon_help_upload = '<p>' . __( 'Upload a sermon by clicking the "Upload Sermon" button. To finish the upload, in the media upload box, click "Save all changes", "Insert into post" or close the dialog box.' ) . '</p>' .
				'<p>' . __( 'The sermons will appear in the sermon list area below this help area.') . '</p>' .
				'<p>' . __( 'Click the "Import" button to publish the individual sermon.' ) . __( ' or click the "Import as draft" button to publish the individual sermon as a draft. The sermon can then be published from the Sermons page.' ) . '</p>'.
				'<p>' . __( 'Click the "Details" button view the details (Sermon Manager and ID3 information) about the individual sermon.' ) . '</p>'.
				'<p>' . __( 'Click the "Import all Sermons" button to attempt to publish all sermons. <br /> Depending on your server configuration, your server may stop processing before all the sermons are imported. In this case, refresh the page, click "Import all sermons" again until all sermons are imported.' ) . '</p>';

				$sermon_help_details = '<p>' . __( 'Files are uploaded to ' ) . $this->folder_path . ' and moved on posting to'. $this->base_path . '.</p>' .
				'<p>' . __( 'This plugin only searchs for mp3 files.' ) . '</p>';

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
	}

	/**
	 * NOTE:  Filters are points of execution in which WordPress modifies data
	 *        before saving it or sending it to the browser.
	 *
	 *        WordPress Filters: http://codex.wordpress.org/Plugin_API#Filters
	 *        Filter Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
	 *
	 * @since    0.2.0
	 */
	public function filter_replace_thickbox_text( $translated_text, $text, $domain )
	{
		if ('Insert into Post' == $text) {
			$referer = strpos( wp_get_referer(), 'sermon-manager-import' );
			if ($referer != '') {
				return __( 'Upload Sermon' );
			}
		}

		return $translated_text;
	}

	/**
	* Changes the location of uploads
	*
	* @since 0.1.0
	* 
	**/

	public function filter_sermon_upload_pre_upload( $file )
	{
		if ( $file['type'] == 'audio/mp3' ) {
			add_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );
		}

		return $file;
	}

	public function filter_sermon_upload_post_upload( $file )
	{
		if ( $file['type'] == 'audio/mp3' ) {
			remove_filter( 'upload_dir', array( $this , 'sermon_upload_custom_upload_dir' ) );
		}

		return $file;
	}

	public function sermon_upload_custom_upload_dir( $path )
	{
		if ( !empty( $path['error'] ) ) { return $path; } //error; do nothing.
		$customdir      = '/' . $this->options['upload_folder'];
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
	 *
	 * @since 0.1.0
	 *  
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

}

//sdg
