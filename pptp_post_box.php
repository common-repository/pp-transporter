<?php

class pptpPostBox 
{

    const META_TRANSPORT = '_pptp_transport';
    const META_POST_ID = '_pptp_post_id';
    const META_POST_URL = '_pptp_post_url';

    public function __construct()
    {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
        add_action( 'transition_post_status', array( $this, 'on_all_status_transitions' ), 10, 3 );
    }

    public function add_meta_box( $post_type ) {

        $post_types = array( 'post', 'page' );

        if ( in_array( $post_type, $post_types )) {
            add_meta_box(
                'pptp_meta_box',
                __( 'PP Transporter', 'ppTranspoter' ),
                array( $this, 'render_meta_box_content' ),
                $post_type,
                'advanced',
                'high'
            );
        }
    }

    public function on_all_status_transitions( $new_status, $old_status, $post ){

        file_put_contents( '_new_status.txt', $new_status );
        file_put_contents( '_old_status.txt', $old_status );

        //var_dump( $new_status, $old_status, $post  );
        if( 'publish' == $new_status ) {
		    $pptpTransport = get_post_meta( $post->ID, self::META_TRANSPORT, true );

            if( 'true' == $pptpTransport ) {
                $pptpPost = $this->xmlrpcPost( $post->ID );
                update_post_meta( $post->ID, self::META_TRANSPORT, false );
                update_post_meta( $post->ID, self::META_POST_ID, $pptpPost['PostId'] );
                update_post_meta( $post->ID, self::META_POST_URL, $pptpPost['url'] );
            }
        }
    }


	public function save( $post_id ) {
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['pptranseporter_inner_custom_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['pptranseporter_inner_custom_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'pptranseporter_inner_custom_box' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */
		// Sanitize the user input.
        if( isset( $_POST['pptp_postid'] ) ) {
            $pptpPostId = sanitize_text_field( $_POST['pptp_postid'] );
            update_post_meta( $post_id, self::META_POST_ID, $pptpPostId );
        }

        if( isset( $_POST['pptp_transport'] ) ) {
            $pptpTransport = sanitize_text_field( $_POST['pptp_transport'] );
            update_post_meta( $post_id, self::META_TRANSPORT, $pptpTransport );
        } else {
            update_post_meta( $post_id, self::META_TRANSPORT, false );
        }

        // XMLRPC Insert/Update
        $post = get_post( $post_id, 'OBJECT' );
        $pptpTransport = get_post_meta( $post_id, self::META_TRANSPORT, true );

        if( 'publish' == $post->post_status && 'true' == $pptpTransport ) {
            var_dump( $post );
            $pptpPost = $this->xmlrpcPost( $post_id );
            update_post_meta( $post_id, self::META_TRANSPORT, false );
            update_post_meta( $post_id, self::META_POST_ID, $pptpPost['PostId'] );
            update_post_meta( $post_id, self::META_POST_URL, $pptpPost['url'] );
        }
	}

    public function xmlrpcPost( $post_id )
    {
        $post = get_post( $post_id, 'OBJECT' );

        include_once( ABSPATH . WPINC . "/class-IXR.php" );
        include_once( ABSPATH . WPINC . "/class-wp-http-ixr-client.php");

        $option = get_option( 'pptransporter_options' );
        $blog_id    = $option['blogid'];
        $username   = $option['username'];
        $password   = $option['password'];

		$pptpPostId = get_post_meta( $post_id, self::META_POST_ID, true );

        $ixrClient = new WP_HTTP_IXR_Client( $option['endpoint']);

        $response = array();
        if( empty( $pptpPostId ) ) {
            //new post
            $ixrClient->query( 'wp.newPost',
                                $blog_id, $username, $password,
                                array(
                                    'post_type' => $post->post_type,
                                    'post_status' => $post->post_status,
                                    'post_title' => $post->post_title,
                                    'post_author' => $post->post_author,
                                    'post_excerpt' => $post->post_excerpt,
                                    'post_content' => $post->post_content,
                                    'post_date' => $post->post_date,
                                    'post_name' => $post->post_name,
                                    'post_password' => $post->post_password,
                                    'comment_status' => $post->comment_status,
                                    'ping_status' => $post->ping_status,
                                    'post_parent' => $post->post_parent,
                                )
                            );
            $result['PostId'] = $ixrClient->getResponse();

            // get url 
            $ixrClient->query( 'wp.getPost',
                                $blog_id, $username, $password, $result['PostId'] );
            $pptpPost = $ixrClient->getResponse();
            $result['url'] = $pptpPost['link'];

        } else {
            //edit post
            $ixrClient->query( 'wp.getPost',
                                $blog_id, $username, $password, $pptpPostId);

            $response = $ixrClient->getResponse();
            $result['url'] = $response['link'];
            $result['PostId'] = $pptpPostId;

            $updPost = array();
            foreach ($response as $key => $value) {
                switch ( $key ) {
                    // no update
                    case 'post_id':
                    case 'guid':
                    case 'post_thumbnail':
                    case 'terms':
                    case 'custom_fields':
                    case 'link':
                    case 'post_date':
                    case 'post_date_gmt':
                    case 'post_modified_gmt':
                        break;
                    
                    default:
                    //update
                        if( $value != $post->{$key} ) {
                            $updPost[$key] = $post->{$key};
                        }
                        break;
                }
            }

            $ixrClient->query( 'wp.editPost',
                                $blog_id, $username, $password, $pptpPostId,
                                $updPost
                            );
            $response = $ixrClient->getResponse();
        }

        return $result;
    }

	public function render_meta_box_content( $post ) {
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'pptranseporter_inner_custom_box', 'pptranseporter_inner_custom_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$pptpPostId = get_post_meta( $post->ID, self::META_POST_ID, true );
		$pptpPostUrl = get_post_meta( $post->ID, self::META_POST_URL, true );

        echo '<table>';

        echo "<tr>";
        echo "<th>";
        printf( 
            '<label for="pptp_transport">%s</label>',
            __( 'Transport', 'ppTranspoter' )
        );
        echo "</th>";
        echo "<td>";
        echo '<input type="checkbox" id="pptp_transport" name="pptp_transport" value="true" />';
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        printf( 
            '<label for="pptp_postid">%s</label>',
            __( 'XML-RPC Post id', 'ppTranspoter' )
        );
        echo "</th>";
        echo "<td>";
        printf( 
            '<input type="number" id="pptp_postid" name="pptp_postid" value="%s" size="10" min="1" />',
            esc_attr( $pptpPostId )
        );
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        printf( 
            '<label for="pptp_url">%s</label>',
            __( 'XML-RPC Post URL', 'ppTranspoter' )
        );
        echo "</th>";
        echo "<td>";
        printf( 
            '<input type="text" id="pptp_url" name="pptp_url" value="%s" size="50" min="1" readonly />',
            esc_attr( $pptpPostUrl )
        );
        printf(
            ' <a href="%s" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a>',
            esc_attr( $pptpPostUrl )
        );
        echo '</td>';
        echo "</tr>";

        echo '</table>';
	}

}