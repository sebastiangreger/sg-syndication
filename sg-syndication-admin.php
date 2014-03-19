<?php


/**
* Block access if called directly
*/
if ( !function_exists( 'add_action' ) ) {
    echo "This is a plugin file, direct access denied!";
    exit;
}


/**
 * sgSyndicationAdmin Plugin Class
 *
 * @author Sebastian Greger
 */
class sgSyndicationAdmin {


    /**
    * create the hooks required to run this plugin
    */
   public function __construct() {
        global $prefix, $services;


        // global prefix for all database fields
        $prefix = 'sg_syndication';

        // definition of services and required database fields
        $services = array(

            // set up twitter.com
            array(  
                'name'      => 'twitter',           // a unique name for this service
                'service'   => 'twitter.com',       // string indicating a service supported by this plugin (currently supported: twitter.com, flickr.com)
                'title'     => 'Twitter',           // name of the service as shown to the user
                'type'      => 'text',              // string indicating the type of service (currently supported: text, photo)
                'intro'     =>                      // intro text shown on the admin settings page
                                'Set up a new application on <a href="https://dev.twitter.com/apps" target="_blank">dev.twitter.com/apps</a>, give it read&write access rights and then generate API key and secret (API keys > Application settings) as well as Access token and Access token secret (API keys > Your access token).',  
                'fields'    => array(               // array of fields that are stored in the database (filled in on the settings page)
                    array(
                        'name'          => 'alias',                                 // field id
                        'label'         => 'Twitter handle',                        // label shown on the settings page form
                        'description'   => 'Twitter username without the @-sign',   // additional info for the settings page form
                        'type'          => 'input',                                 // input field format (currently only supports: input)
                        'required'      => true,                                    // values that are required in order for syndication to work
                    ),
                    array(
                        'name'          => 'key',
                        'label'         => 'API key',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'secret',
                        'label'         => 'API secret',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'token',
                        'label'         => 'Access token',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'tokensecret',
                        'label'         => 'Access token secret',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                ),
            ),

            // set up flickr.com
            array( 
                'name'      => 'flickr',  
                'service'   => 'flickr.com',
                'title'     => 'Flickr',  
                'type'      => 'photo',
                'intro'     => 'Getting the API access variables from Flickr is somehat complicated. First create a Flickr app at <a href="https://secure.flickr.com/services/apps/create/apply/">flickr.com/services/apps/create/apply/</a>, which will give you the API key and secret. Then, with the help of the tool at <a href="http://phpflickr.com/tools/auth/">phpflickr.com/tools/auth/</a>, retrieve the required (permanent) API token.',
                'fields'    => array(
                    array(
                        'name'          => 'alias',
                        'label'         => 'Flickr username',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'nsid',
                        'label'         => 'Numeric user ID',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'key',
                        'label'         => 'API key',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'secret',
                        'label'         => 'API secret',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                    array(
                        'name'          => 'token',
                        'label'         => 'API token',
                        'description'   => '',
                        'type'          => 'input',
                        'required'      => true,
                    ),
                ),
            ),

        );

        // display the syndication box in post ui
        add_action('add_meta_boxes', array( 'sgSyndicationAdmin', 'add_the_meta_box' ) );

        // add options page to admin ui
        add_action('admin_menu', array( 'sgSyndicationAdmin', 'settings' ) );

        // check for syndication fields and, if applicable, send the syndication messages
        add_action('publish_post', array( 'sgSyndicationAdmin', 'syndicate' ) );
        add_action('admin_notices', array( 'sgSyndicationAdmin', 'session_admin_notice' ) );


    }


    /**
    * Trigger syndication of messages
    */
    public function syndicate( $post_id ) {


        // Flickr
        if ( $_POST['sg_syndication_flickr_toggle'] == '1' && $_POST['sg_syndication_flickr_title'] != '' ) {

            // call the flickr handler
            sgSyndicationAdmin::syndicate_flickr( $post_id, $_POST['sg_syndication_flickr_text'], $_POST['sg_syndication_flickr_title'] );

        }


        // Twitter
        if ( $_POST['sg_syndication_twitter_toggle'] == '1' && $_POST['sg_syndication_twitter_text'] != '' ) {

            // call the twitter handler
            sgSyndicationAdmin::syndicate_twitter( $post_id, $_POST['sg_syndication_twitter_text'] );

        }


    }


    /**
    * Syndicate message to Flickr
    */
    public function syndicate_flickr( $post_id, $possetext, $possetitle ) {


        // TODO: validate that $possetext contains a valid shortlink

        // check if post has been syndicated to flickr already by looking up the meta field used for storage of syndicated copies
        if ( get_post_meta( $post->ID, $prefix . '_flickr', true ) != '' ) {
            $urlindb = get_post_meta( $post->ID, $prefix . '_flickr', true );
            set_transient( get_current_user_id().'syndicationerror', 'Post already syndicated to Flickr: <a href="' . $urlindb . '" target="_blank">' . $urlindb . '</a>' );
            return;
        }

        // return error if post does not have a featured image
        if ( !has_post_thumbnail() ) {
            set_transient( get_current_user_id().'syndicationerror', 'Post does not have a featured image to syndicate to Flickr.' );
            return;
        } else {
            // ...otherwise construct a variable with the file location for the image upload
            $thumb_url = wp_get_attachment_metadata( get_post_thumbnail_id($post->ID) );
            $upload_dir = wp_upload_dir();
            $imagepath = $upload_dir['basedir'] . '/' . $thumb_url['file'];
        }

        // require phpflickr
        require 'phpFlickr/phpFlickr.php';

        // instantiate the phpFlickr class with the API details
        $fl = new phpFlickr( get_option( 'sg_syndication_flickr_key' ), get_option( 'sg_syndication_flickr_secret' ), true);

        // set the token and authenticate with the API based on the provided API key/secret
        $fl->setToken( get_option( 'sg_syndication_flickr_token' ) );
        $fl->auth('write');

        // execute the synchronous image upload and capture the api response
        $apireturn = $fl->sync_upload(
            $imagepath,                     // photo: The file to upload.
            stripslashes( $possetitle ),    // title (optional): The title of the photo.
            stripslashes( $possetext ),     // description (optional): A description of the photo. May contain some limited HTML.
            '',                             // tags (optional): A space-seperated list of tags to apply to the photo.
            '1',                            // is_public (optional): Set to 0 for no, 1 for yes. Specifies who can view the photo.
            '1',                            // is_friend (optional): Set to 0 for no, 1 for yes. Specifies who can view the photo.
            '1',                            // is_family (optional): Set to 0 for no, 1 for yes. Specifies who can view the photo.
            '1',                            // safety_level (optional): Set to 1 for Safe, 2 for Moderate, or 3 for Restricted.
            '1',                            // content_type (optional): Set to 1 for Photo, 2 for Screenshot, or 3 for Other.
            '2'                             // hidden (optional): Set to 1 to keep the photo in global search results, 2 to hide from public searches. 
        );

        // by default, api returns the photo id, which is a very long number
        if ( $apireturn > 9999 ) {

            // build the tweet url based on the api response data
            $posseurl = 'https://secure.flickr.com/photos/' . get_option( 'sg_syndication_flickr_nsid' ) . '/' . $apireturn .'/';

            // save tweet url into db meta field 'sg_syndication_twitter'
            update_post_meta( $post_id, 'sg_syndication_flickr', $posseurl );

            // create success message
            set_transient( get_current_user_id().'syndicationsuccess', $posseurl );

        } else {

            // create error message / error codes see https://secure.flickr.com/services/api/upload.api.html
            set_transient( get_current_user_id().'syndicationerror', $apireturn );

        }


    }


    /**
    * Syndicate message to Twitter
    */
    public function syndicate_twitter( $post_id, $possetext ) {


        // TODO: validate that $possetext contains a valid shortlink

        // check if post has been syndicated to twitter already by looking up the meta field used for storage of syndicated copies
        if ( get_post_meta( $post->ID, $prefix . '_twitter', true ) != '' ) {
            $urlindb = get_post_meta( $post->ID, $prefix . '_twitter', true );
            set_transient( get_current_user_id().'syndicationerror', 'Post already syndicated to Twitter: <a href="' . $urlindb . '" target="_blank">' . $urlindb . '</a>' );
            return;
        }

        // require codebird
        require_once( dirname( __FILE__ ) . '/codebird-php/src/codebird.php');

        // establish api connection         
        \Codebird\Codebird::setConsumerKey( get_option( 'sg_syndication_twitter_key' ) , get_option( 'sg_syndication_twitter_secret' ) );
        $cb = \Codebird\Codebird::getInstance();
        $cb->setToken( get_option( 'sg_syndication_twitter_token' ), get_option( 'sg_syndication_twitter_tokensecret' ) );
        $cb->setReturnFormat(CODEBIRD_RETURNFORMAT_ARRAY);

        // send the tweet and capture the api response
        $params = array(
            'status' => stripslashes( $possetext ),
        );
        $apireturn = $cb->statuses_update( $params );

        if ( $apireturn['httpstatus'] == '200' ) {

            // build the tweet url based on the api response and save it into db meta field 'sg_syndication_twitter'
            $posseurl = 'https://twitter.com/' . $apireturn['user']['screen_name'] . '/status/' . $apireturn['id_str'];
            update_post_meta( $post_id, 'sg_syndication_twitter', $posseurl );

            // create success message
            set_transient( get_current_user_id().'syndicationsuccess', $posseurl );

        } else {

            // create error message (returned array: [request] => '/1.1/statuses/update.json', [error] => Read-only application cannot POST.', [httpstatus] => '401')
            if ( $apireturn['error'] ) {
                set_transient( get_current_user_id().'syndicationerror', $apireturn['error'] );
            } else {
                set_transient( get_current_user_id().'syndicationerror', $apireturn['errors'][0]['message'] . ' (Code ' . $apireturn['errors'][0]['code'] . ')' );
            }

        }


    }


    /**
    * Gives feedback to the admin user after submitting a syndication task
    */
    function session_admin_notice() {


        if ( $out = get_transient( get_current_user_id().'syndicationerror' ) ) {
            delete_transient( get_current_user_id().'syndicationerror' );
            echo '<div class="error"><p>Syndication failed: ' . $out . '</p></div>';
        }

        elseif ( $out = get_transient( get_current_user_id().'syndicationsuccess' ) ) {
            delete_transient( get_current_user_id().'syndicationsuccess' );
            echo '<div class="updated"><p>Syndication successful: <a href="' . $out . '" target="_blank">' . $out . '</a></p></div>';
        }


    }


    /**
    * Adds a meta box
    */
    public function add_the_meta_box() {  


        add_meta_box(  
            'sg_syndication_metabox',                         // $id
            'Syndication',                              // $title  
            array( 'sgSyndicationAdmin', 'render_meta_box' ), // $callback  
            'post',                                     // $page  
            'side',                                     // $context  
            'high'                                      // $priority  
        );


    }  


    /**
    * Fills the data box
    */
    public function render_meta_box( $post ) {  
        global $prefix, $services;


        // Use nonce for verification  
        wp_nonce_field( 'sg_syndication_metabox', 'sg_syndication_metabox_nonce' );

        // loop through all the services
        foreach ($services as $service) {

            // Use get_post_meta to retrieve an existing value from the database.
            $urlindb = get_post_meta( $post->ID, $prefix . '_' . $service['name'], true );

            // check that all required api fields are filled in
            foreach ($service['fields'] as $field) {
                if ( $field['required'] ) {
                    if ( !get_option( $prefix . '_' . $service['name'] . '_' . $field['name'] ) || get_option( $prefix . '_' . $service['name'] . '_' . $field['name'] ) == '' ) {
                        $missingapidata[$service['name']] = true;
                    } else {
                        $servicesetup['any'] = true;
                        $servicesetup[$service['name']] = true;
                    }
                }
            }

            // If db contains link to syndicated silo post, output link
            if ( $urlindb != '' ) {

                echo '
                    <p>
                    <label>
                    <input type="checkbox" disabled="disabled" checked="checked" />
                    Post syndicated to <a href="' . $urlindb . '" target="_blank">' . $service['title'] . '</a>
                    </label>
                    </p>
                ';

            // if the earlier check has shown that not all api data for this service has been filled in, show link to settings
            } elseif ( $servicesetup[$service['name']] && $missingapidata[$service['name']] ) {

                echo '
                    <p>
                    <label>
                    <input type="checkbox" disabled="disabled" />
                    <span style="opacity:.4;">' . $service['title'] . ' API <a href="options-general.php?page=sg_syndication_settings">settings</a> incomplete</span>
                    </label>
                    </p>
                ';

            // if db does not contain syndicated URL, present the UI
            } elseif ( $servicesetup[$service['name']] ) {

                // Display the checkbox toggle
                echo '
                    <p>
                    <label>
                    <input
                        id="' . $prefix . '_' . $service['name'] .'_toggle"
                        type="checkbox"
                        name="' . $prefix . '_' . $service['name'] .'_toggle"
                        value="1"
                ';
                if ( $service['type'] == 'text' ) {
                    echo'
                        onClick="
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_ui\').toggle();
                            jQuery(\'#sg_syndication_tip\').show();
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_text\').html( jQuery(\'#title\').val() + \' ' . wp_get_shortlink() . '\' );
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_counter span\').html(jQuery(\'#' . $prefix . '_' . $service['name'] .'_text\').val().length);
                        "
                    ';
                }
                elseif ( $service['type'] == 'photo' ) {
                    echo'
                        onClick="
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_ui\').toggle();
                            jQuery(\'#sg_syndication_tip\').show();
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_title\').val( jQuery(\'#title\').val() );
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_text\').html( \'Syndicated copy; see original version at ' . wp_get_shortlink() . '\' );
                            jQuery(\'#' . $prefix . '_' . $service['name'] .'_img\').attr(\'src\', jQuery(\'#set-post-thumbnail img\').attr(\'src\'));
                        "
                    ';
                }
                echo '
                    >
                    ' . $service['title'] . ' (' . get_option('' . $prefix . '_' . $service['name'] .'_alias') . ')
                    </label>
                    </p>
                    <script>
                    document.getElementById(\'' . $prefix . '_' . $service['name'] .'_toggle\').checked = false;
                    </script>
                ';

                // Display the input form
                echo '
                    <div id="' . $prefix . '_' . $service['name'] .'_ui" style="display:none;">
                ';
                if ( $service['type'] == 'text' ) {
                    echo '                
                        <p style="margin-bottom:0;">
                        <textarea id="' . $prefix . '_' . $service['name'] .'_text"
                            name="' . $prefix . '_' . $service['name'] .'_text"
                            style="margin-left:8%; width:92%;"
                            rows="6"
                            onkeyup="
                                len=jQuery(this).val().length;
                                jQuery(\'#' . $prefix . '_' . $service['name'] .'_counter span\').html(len);
                                if(len>140){jQuery(\'#' . $prefix . '_' . $service['name'] .'_counter\').css(\'color\',\'red\');}else{jQuery(\'#' . $prefix . '_' . $service['name'] .'_counter\').css(\'color\',\'#444\');}
                            "
                        ></textarea>
                        </p>
                        <p style="font-size:80%;text-align:right;margin-top:0;" id="' . $prefix . '_' . $service['name'] .'_counter">
                        <span>0</span>/140 characters
                        </p>
                    ';
                }
                elseif ( $service['type'] == 'photo' ) {
                    echo '
                        <p>
                        <input id="' . $prefix . '_' . $service['name'] .'_title"
                            type = "text"
                            name="' . $prefix . '_' . $service['name'] .'_title"
                            style="margin-left:8%; width:92%;"
                        />
                        </p>                        
                        <img
                            id="' . $prefix . '_' . $service['name'] .'_img"
                            style="margin-left:8%; width:92%; height:auto;"
                            src=""
                        />
                        <p style="margin-bottom:0;">
                        <textarea id="' . $prefix . '_' . $service['name'] .'_text"
                            name="' . $prefix . '_' . $service['name'] .'_text"
                            style="margin-left:8%; width:92%;"
                            rows="6"
                        ></textarea>
                        </p>
                    ';
                }
                echo '
                    </div>
                ';

            }

        }

        echo '
            <p
                id="sg_syndication_tip"
                style="display:none;font-weight:bold;text-align:center;"
            >
            "Publish/Update" post to syndicate
            </p>
        ';


        if ( !$servicesetup['any'] ) {
            echo '
                <p style="opacity:.4;">You currently do not have any external services <a href="options-general.php?page=sg_syndication_settings">set up</a> for syndication</p>
            ';
        }

    }


    /**
    * Adds an options page to the admin UI
    */
    public function settings() {


        add_options_page(
            'Syndication settings',         // HTML page title
            'Syndication',                  // Left menu title
            'administrator',                // capability required for this admin page
            'sg_syndication_settings',            // unique key
            array(                          // function for HTML content
                'sgSyndicationAdmin',
                'settings_html'
            )
        );


    }


    /**
    * Outputs the HTML for the options page
    */
    public function settings_html() {
        global $prefix, $services;


        // output page head
        $html = '
            <div class="wrap">
            <h2>Syndication Settings</h2>
            <form action="options.php" method="post" name="options">
            <p>The plugin can only syndicate posts to silos if all the required APIs are set up. Enter all API data in the form below.</p>
            <p>Please note that this is an experimental plugin. Always check your Twitter feed after working with the plugin and remember that any admin user on your WordPress blog can use this to post to your Twitter feed (the plugin should only be used on single-user installations).</p>
        ';

        // nonce field for verification
        $html .= wp_nonce_field('update-options');

        // variable collecting all field names for the hidden field on submission
        $submission = '';

        // loop through all the services
        foreach ($services as $service) {

            // service head
            $html .= '
                <h3 class="title">' . $service['title'] . '</h3>
                <p>' . $service['intro'] . '</p>
                <table class="form-table">
            ';

            foreach ($service['fields'] as $field) {

                // create the name of the variable from prefix, service name and field name
                $variable = $prefix . '_' . $service['name'] . '_' . $field['name'];

                // attach the variable name to the string for the hidden field on submission
                $submission .= $variable . ',';

                // retrieve the value from the db, if exists
                $value = (get_option( $variable ) != '') ? get_option( $variable ) : '';

                // output the form element
                if ( $field['type'] == 'input' ) {

                    $html .='
                        <tr valign="top">
                        <th><label for="' . $variable . '">' . $field['label'] . '</label></th>
                        <td class="row">
                        <input type="text" name="' . $variable . '" class="regular-text code" value="' . $value . '" />
                        <p class="description">' . $field['description'] . '</p>
                        </td>
                        </tr>
                    ';

                }

            }

            // service foot
            $html .= '</table>';

        }

        // submission elements
        $html .= '
            <input type="hidden" name="page_options" value="' . $submission . '" />
            <input type="hidden" name="action" value="update" />
            <p class="submit"><input type="submit" name="Submit" id="submit" class="button button-primary" value="Save changes" /></p>
            </form></div></div>
        ';

        // output the html
        echo $html;


    }


}


$sgsyndicationadmin = new sgSyndicationAdmin();