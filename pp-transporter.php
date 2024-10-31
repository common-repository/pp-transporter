<?php
/**
 * Plugin Name:     PP Transporter
 * Plugin URI:      https://github.com/yyano/pp-transporter
 * Description:     Post/Page Transport via XML-RPC.
 * Author:          YANO Yasuhiro
 * Author URI:      https://plus.google.com/u/0/+YANOYasuhiro/
 * Text Domain:     ppTransporter
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Pp_Transporter
 */

class ppTransporter 
{
    public function __construct()
    {
        // Admin setting page
        require_once 'pptp_admin_page.php';
        $pptpAdminPage = new pptpAdminPage();

        require_once 'pptp_post_box.php';
        $pptpPostBox = new pptpPostBox();
    }
}

if( is_admin() ){
    new ppTransporter();
}