<?php
/**
 * @package Grabbber
 * @version 1.3
 */
/*
Plugin Name: Grabbber
Description: Plugin will grab freebie items from the Dribbble API and generate content on native site
Version: 1.5.1
Author: Evan D'Elia
Author URI: http://www.pixelpusher.ninja
*/

require_once 'Client.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function new_attachment($att_id){
    // the post this was sideloaded into is the attachments parent!
    $p = get_post($att_id);
    update_post_meta($p->post_parent,'_thumbnail_id',$att_id);
}

class Grabbber {

private $options_name = "grabbber";
private $default_options = array(
    'custom' => 'free',
    'draft' => 'true'
);

function __construct(){
    //add_action('init', $this->check_sched());
    
    if ( ! wp_next_scheduled( 'my_task_hook' ) ) {
  		wp_schedule_event( time(), 'hourly', 'my_task_hook' );
	}
	
	add_action('my_task_hook', $this->grab_freebies());

	add_action('admin_init', array($this, 'admin_init'));
	
	add_action('admin_menu', array($this, 'add_page'));
	
	register_activation_hook(ABSPATH . 'wp-content/plugins/Grabbber/untitled.php', array($this, 'on_activate'));
	register_deactivation_hook(ABSPATH . 'wp-content/plugins/Grabbber/untitled.php', array($this, 'on_deactivate'));
	register_uninstall_hook(ABSPATH . 'wp-content/plugins/Grabbber/untitled.php', array($this, 'on_uninstall'));
}

public function on_activate() {
    update_option($this->options_name, $this->default_options);
}

public function on_deactivate() {
    delete_option($this->option_name);
}

public function on_uninstall()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        check_admin_referer( 'bulk-plugins' );

        // Important: Check if the file is the one
        // that was registered during the uninstall hook.
        // can also use plugin_dir_path( __FILE__ ) . '/untitled.php' to get path!
        if (ABSPATH . 'wp-content/plugins/Grabbber/untitled.php' != WP_UNINSTALL_PLUGIN )
            return;

        # Uncomment the following line to see the function in action
        # exit( var_dump( $_GET ) );
    }

public function admin_init() {
    register_setting('grabbber_options', $this->options_name);
}

public function add_page() {
    add_options_page('Grabbber Options', 'Grabbber Options', 'manage_options', 'grabbber_options', array($this, 'options_do_page'));
}

public function options_do_page() {
    $options = get_option($this->options_name);
    ?>
    <div class="wrap">
        <h2>Grabbber Options</h2>
        <hr>
        <p>Here you can set what Dribbble tags Grabbber will search for when adding content to your site!</p>
        <br>
        <form method="POST" action="options.php">
        	<?php settings_fields('grabbber_options'); ?>

  			<br>
  			Below you may enter any number of tags which you want to search for. Grabbber will then create posts for any Dribbble shot which contains at least one of these tags.<br>
  			Enter each tag in all lowercase letters, each separated by a single space. <br><br>
  			<input type="text" name="<?php echo $this->options_name?>[custom]" value="<?php echo $options['custom']; ?>" style="width: 50%;"> <br><br><br>
  			
  			
  			The box below determines whether Grabbber will create posts as drafts or automatically publish them. If you would like to automatically publish posts, leave the box unchecked<br>
  			Create Posts as Drafts  <input type="checkbox" name="<?php echo $this->options_name?>[draft]" value="true" <?php if ($options[draft]==true) echo 'checked="checked" '; ?>> <br>
  			  			
  			<p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
  		</form>
        
    </div>
    <?php
}

// This just echoes the chosen line, we'll position it later
public function grab_freebies() {
	$client = new Client;
	$results = get_option($this->options_name);
	$results = explode(" ", $results["custom"]);
    $shots = $client->getShotsList();
    foreach ($shots as $shot){
    	foreach ($shot->tags as $tag){
    		if (in_array($tag, $results)){
    			$src = $shot->images->normal;
    			$username = $shot->user->username;
    			//echo "<div id='freebie'><img src=$src /><p>$shot->title</p><p>$shot->description</p></div>";
    			$skip = FALSE;
    			$args = array( 'numberposts' => '8' );
				$recent_posts = wp_get_recent_posts( $args );
				foreach( $recent_posts as $recent ){
					if ($recent["post_title"] === $shot->title) $skip=TRUE;
				}
    			
    			if (!$skip){
    			$post = array(
    				//'ID'             => 57, // Are you updating an existing post?
  'post_content'   => "<p>$shot->description</p>This post was created by <a href=https://dribble.com/$username>$username</a> and can be viewed on dribbble <a href=https://dribbble.com/shots/$shot->id>here</a>", // The full text of the post.
  'post_name'      => $shot->title, // The name (slug) for your post
  'post_title'     => $shot->title, // The title of your post.
  'post_status'    => ($results["draft"]==true) ? 'publish' : 'draft', // Default 'draft'.
  'post_excerpt'   => "<p>$shot->description</p>This post was created by <a href=https://dribble.com/$username>$username</a> and can be viewed on dribbble <a href=https://dribbble.com/shots/$shot->id>here</a>",
  'tags_input'     => $shot->tags
    			);
    			
    			//error_reporting(0);
    			//$upload_dir = wp_upload_dir();
				//$image_data = file_get_contents($src);
				//$filename = basename($src);
				//if(wp_mkdir_p($upload_dir['path']))
    			//	$file = $upload_dir['path'] . '/' . $filename;
				//else
    			//	$file = $upload_dir['basedir'] . '/' . $filename;
				//file_put_contents($file, $image_data);


    			$post_id = wp_insert_post($post, TRUE);
    			
    			add_action('add_attachment','new_attachment');
    			
				$attach_id = media_sideload_image( $src, $post_id, $post_id);
    			
				// we have the Image now, and the function will have fired too setting the thumbnail ID in the process, so lets remove the hook so we don't cause any more trouble 
				remove_action('add_attachment','new_attachment');

    		}
    		}
    	}
	}
}


// Now we set that function up to execute when the admin_notices action is called
//add_action( 'admin_notices', 'grab_freebies' );

}

$wpDribbleAPI = new Grabbber();
?>
