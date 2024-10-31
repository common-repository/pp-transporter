<?php

class pptpAdminPage 
{
    const OPTION_NAME = 'pptransporter_options';

    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'pptp_admin_styles' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            __( 'PP Transporter', 'ppTransporter' ), 
            'manage_options', 
            'pptransport-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        //add_action( 'admin_print_styles', 'pptp_admin_styles' );

        // Set class property
        $this->options = get_option( self::OPTION_NAME );
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'PP Transporter', 'ppTransporter' ) ?></h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'pptp_xmlrpc_group' );   
                do_settings_sections( 'pptp-setting-page' );
                submit_button(); 
            ?>
            </form>
        </div>
<?php
    }

    public function pptp_admin_styles()
    {
        wp_enqueue_style( 'ppTransporter', "//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.css" );
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'pptp_xmlrpc_group', // Option group
            self::OPTION_NAME, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'pptp_xmlrpc_setting', // ID
            'XML-RPC', // Title
            array( $this, 'section_info_xmlrpc' ), // Callback
            'pptp-setting-page' // Page
        );  

        add_settings_field(
            'endpoint', // ID
            __( 'Endpoint', 'ppTransporter' ), // Title 
            array( $this, 'field_endpoint' ), // Callback
            'pptp-setting-page', // Page
            'pptp_xmlrpc_setting' // Section           
        );      

        add_settings_field(
            'blogid', // ID
            __( 'Blog ID', 'ppTransporter' ), // Title 
            array( $this, 'field_blogid' ), // Callback
            'pptp-setting-page', // Page
            'pptp_xmlrpc_setting' // Section           
        );      

        add_settings_field(
            'username',
            __( 'Username', 'ppTransporter' ),
            array( $this, 'field_username' ),
            'pptp-setting-page',
            'pptp_xmlrpc_setting'         
        );      

        add_settings_field(
            'password', 
            __( 'Password', 'ppTransporter' ), 
            array( $this, 'field_password' ), 
            'pptp-setting-page', 
            'pptp_xmlrpc_setting'
        );

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();

        if( isset( $input['endpoint'] ) )
            $new_input['endpoint'] = sanitize_text_field( $input['endpoint'] );

        if( isset( $input['blogid'] ) )
            $new_input['blogid'] = sanitize_text_field( $input['blogid'] );

        if( isset( $input['username'] ) )
            $new_input['username'] = sanitize_text_field( $input['username'] );

        if( isset( $input['password'] ) )
            $new_input['password'] = sanitize_text_field( $input['password'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function section_info_xmlrpc()
    {
        echo '<p>接続先のWordpressのXMLRPCエンドポイントを設定します。</p>';
        echo '<p>Set the destination XMLRPC endpoint of Wordpress.</p>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function field_endpoint()
    {
        printf(
            '<input type="text" id="endpoint" name="%s[endpoint]" value="%s" class="regular-text" />',
            self::OPTION_NAME,
            isset( $this->options['endpoint'] ) ? esc_attr( $this->options['endpoint']) : ''
        );        
    }

    public function field_blogid()
    {
        printf(
            '<input type="number" id="blogid" name="%s[blogid]" value="%s" min="1" />',
            self::OPTION_NAME,
            isset( $this->options['blogid'] ) ? esc_attr( $this->options['blogid']) : ''
        );        
    }

    public function field_username() {
        printf(
            '<input type="text" id="username" name="%s[username]" value="%s" />',
            self::OPTION_NAME,
            isset( $this->options['username'] ) ? esc_attr( $this->options['username']) : ''
        );     
    }

    public function field_password() {
        printf(
            '<input type="password" id="password" name="%s[password]" value="%s" />',
            self::OPTION_NAME,
            isset( $this->options['password'] ) ? esc_attr( $this->options['password']) : ''
        );     
    }

}