<?php
class SermonManagerImportSettings {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Default import settings
     */
    private $defaults = array(
        'sermon_title' => 'title',
        'preacher' => 'artist',
        'sermon_series' => 'album',
        'sermon_topics' => 'genre',
        'sermon_description' => 'comment',
        'bible_passage' => 'composer',
        'publish_status' => 'draft',
        'bible_book_series' => '0',
        'upload_folder' => 'sermon-manager-import',
        'date' => 'subtitle',
        'date_format' => 'DDMMYYYY',
        'date_year_split' => 50,
        'am_service' => 'Sunday Morning',
        'pm_service' => 'Sunday Evening'
    );

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_options_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_options_page() {
        if ( ! is_plugin_active( 'sermon-manager-for-wordpress/sermons.php' ) ) {
            add_plugins_page(
                __( 'Sermon Manager Import Settings', 'sermon-manager-import' ),
                __( 'Import Settings', 'sermon-manager-import' ),
                'manage_options',
                'sermon-manager-import-options',
                array( $this, 'create_admin_page' )
            );
        }
        else {
            add_submenu_page(
                'edit.php?post_type=wpfc_sermon',
                __( 'Import Options', 'sermon-manager-import' ),
                __( 'Import Options', 'sermon-manager-import' ),
                'manage_options',
                'sermon-manager-import-options',
                array( $this, 'create_admin_page' )
            );
        }
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = wp_parse_args( get_option( 'smi_options' ), $this->defaults );
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Sermon Manager Import Settings</h2>
            <form method="post" action="options.php">
            <?php
        // This prints out all hidden setting fields
        settings_fields( 'smi_options' );
        do_settings_sections( 'smi-settings' );
        submit_button();
?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            'smi_options', // Option group
            'smi_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_import', // ID
            'ID3 Tag Mapping', // Title
            array( $this, 'print_section_info_import' ), // Callback
            'smi-settings' // Page
        );

        add_settings_field(
            'sermon_title',
            'Sermon Title',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'sermon_title'
            )
        );

        add_settings_field(
            'preacher',
            'Preacher',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'preacher'
            )
        );

        add_settings_field(
            'sermon_series',
            'Sermon Series',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'sermon_series'
            )
        );

        add_settings_field(
            'bible_book_series',
            'Bible book series',
            array( $this, 'bible_book_series_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'bible_book_series'
            )
        );

        add_settings_field(
            'sermon_topics',
            'Sermon Topics',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'sermon_topics'
            )
        );

        add_settings_field(
            'sermon_description',
            'Sermon Description',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'sermon_description'
            )
        );

        add_settings_field(
            'bible_passage',
            'Bible Passage',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'bible_passage'
            )
        );

        add_settings_field(
            'date',
            'Date',
            array( $this, 'settings_option_callback' ),
            'smi-settings',
            'setting_section_import',
            array (
                'date',
                'Filename'
            )
        );

        /**
         * Other Import Settings
         */

        add_settings_section(
            'setting_section_other',
            'Import Settings',
            array( $this, 'print_section_info_other' ),
            'smi-settings'
        );

        add_settings_field(
            'publish_status',
            'Publish Status',
            array( $this, 'publish_status_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'publish_status'
            )
        );

        add_settings_field(
            'upload_folder',
            'Upload folder name',
            array( $this, 'upload_folder_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'upload_folder'
            )
        );

        add_settings_field(
            'date_format',
            'Date Format',
            array( $this, 'date_format_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'date_format'
            )
        );

        add_settings_field(
            'date_year_split',
            'Two Digit Year',
            array( $this, 'date_year_split_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'date_year_split'
            )
        );

        add_settings_field(
            'am_service',
            'AM Meridiem',
            array( $this, 'am_service_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'am_service'
            )
        );

        add_settings_field(
            'pm_service',
            'PM Meridiem',
            array( $this, 'pm_service_callback' ),
            'smi-settings',
            'setting_section_other',
            array (
                'pm_service'
            )
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array   $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        foreach ( $input as $key => $opt ) {
            if ( !empty( $input[$key] ) )
                $input[$key] = sanitize_text_field( $input[$key] );
        }

        return $input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info_import() {
        print 'Select an ID3 tag for each Sermon property.';
    }

    /**
     * Print the Section text
     */
    public function print_section_info_other() {
        print 'Optional settings';
    }

    /**
     * Publish setting from option array
     */
    public function publish_status_callback( $args ) {
        $selected = esc_attr( $this->options[$args[0]] );

        $options = '<select id="'. $args[0] . '" name="smi_options[' .  $args[0] .']">
            <option value="publish"' . selected( $selected, 'publish', false ) . '>Publish</option>
            <option value="draft"' . selected( $selected, 'draft', false ) . '>Draft</option>
        </select>';

        echo $options;
    }

    /**
     * Get the settings option array for ID3 tag mapping
     */
    public function settings_option_callback( $args ) {
        $selected = esc_attr( $this->options[$args[0]] );
        $date_option = ( isset( $args[1] ) ) ? $args[1] : 'Not used';

        $options = '<select id="'. $args[0] . '" name="smi_options[' .  $args[0] .']">
            <option value="title"' . selected( $selected, 'title', false ) . '>Title</option>
            <option value="subtitle"' . selected( $selected, 'subtitle', false ) . '>Subtitle</option>
            <option value="artist"' . selected( $selected, 'artist', false ) . '>Artist</option>
            <option value="album"' . selected( $selected, 'album', false ) . '>Album</option>
            <option value="genre"' . selected( $selected, 'genre', false ) . '>Genre</option>
            <option value="comment"' . selected( $selected, 'comment', false ) . '>Comment</option>
            <option value="composer"' . selected( $selected, 'composer', false ) . '>Composer</option>
            <option value="picture"' . selected( $selected, 'picture', false ) . '>Attached Picture</option>
            <option value="year"' . selected( $selected, 'year', false ) . '>Year</option>
            <option value=""' . selected( $selected, '', false ) . '>'. $date_option . '</option>
        </select>';

        echo $options;
    }

    /**
     * Use the Bible book from the Bible passage as the series
     */
    public function bible_book_series_callback( $args ) {
        echo '<input type="checkbox" id="'.$args[0].'" name="smi_options['.$args[0].']"  value="1" '. checked( 1, esc_attr( $this->options[$args[0]] ), false ) . '" /> Use the Bible book from the Bible passage as the series.';
    }

    public function upload_folder_callback( $args ) {
        $uploads_details = wp_upload_dir();
        echo '<input type="text" id="'.$args[0].'" name="smi_options['.$args[0].']" value="'.$this->options[$args[0]].'" class="regular-text"> <br />Your upload path is '.$uploads_details['basedir'].'/'.$this->options[$args[0]];
    }

    /**
     * Get the settings option array for date formats
     */
    public function date_format_callback( $args ) {
        $selected = esc_attr( $this->options[$args[0]] );

        $options = '<select id="'. $args[0] . '" name="smi_options[' .  $args[0] .']">
            <option value="YYYYMMDD"' . selected( $selected, 'YYYYMMDD', false ) . '>Year Month Day Meridiem</option>
            <option value="DDMMYYYY"' . selected( $selected, 'DDMMYYYY', false ) . '>Day Month Year Meridiem</option>
        </select>';

        $options .= '<p>Single digit days and months, full english month names, and two digit years are supported. Just make sure they are in the order you specify.<br />The meridiem specifies morning or evening to determine a service and can be any of the following (case insensitive): a, am, morning, p, pm, evening.';

        // <option value="DMMDMYYY"' . selected($selected, 'MMDDYYYY', false) . '>MMDDYYYY</option>
        // <option value="MDYYYY"' . selected($selected, 'MDYYYY', false) . '>MDYYYY</option>

        echo $options;
    }

    /**
     * Get the setting for the two digit year split
     */
    public function date_year_split_callback( $args ) {
        echo '<input type="text" max="99" min="0" id="'.$args[0].'" name="smi_options['.$args[0].']" value="'.$this->options[$args[0]].'" > <br />Year entered determines if a two digit year is from the 1900s or 2000s. The default is 50, so years 50-99 are 1950-1999 and 00-49 are 2000-2049.';
    }

    public function am_service_callback( $args ) {
        $selected = esc_attr( $this->options[$args[0]] );
        $service_types = get_terms( 'wpfc_service_type', array( 'hide_empty' => 0 ) ); //array of objects; name property is what we want
        $options = '<select id="'. $args[0] . '" name="smi_options[' .  $args[0] .']">';
        foreach ( $service_types as $type ) {
            $options .= '<option value="' . $type->name . '"' . selected( $selected, $type->name, false ) . '>' . $type->name . '</option>';
        }
        $options .= '</select>';

        $options .= '<p>Sets the AM meridiem to the selected service type. When a sermon is imported and the date includes a meridiem determined to be AM, the selected service type will be applied.</p>';

        echo $options;
    }

    public function pm_service_callback( $args ) {
        $selected = esc_attr( $this->options[$args[0]] );
        $service_types = get_terms( 'wpfc_service_type', array( 'hide_empty' => 0 ) ); //array of objects; name property is what we want
        $options = '<select id="'. $args[0] . '" name="smi_options[' .  $args[0] .']">';
        foreach ( $service_types as $type ) {
            $options .= '<option value="' . $type->name . '"' . selected( $selected, $type->name, false ) . '>' . $type->name . '</option>';
        }
        $options .= '</select>';

        $options .= '<p>Sets the PM meridiem to the selected service type. When a sermon is imported and the date includes a meridiem determined to be PM, the selected service type will be applied.</p>';

        echo $options;
    }
}

if ( is_admin() )
    $sermon_manager_import_settings = new SermonManagerImportSettings();

//sdg
