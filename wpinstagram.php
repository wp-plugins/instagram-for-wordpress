<?php
/*
	Plugin Name: Instagram for Wordpress
	Plugin URI: http://wordpress.org/extend/plugins/instagram-for-wordpress/
	Description: Simple sidebar widget that shows Your latest 20 instagr.am pictures and picture embeder.
	Version: 0.1.5
	Author: Eriks Remess
	Author URI: http://twitter.com/EriksRemess
*/
add_shortcode('instagram', 'instagram_embed_shortcode');

function instagram_embed_shortcode($atts, $content = null){
		
	extract(shortcode_atts(array(
		'url' => '',
		'size' => 'middle',
		'addlink' => 'yes'
		), $atts));
	
	if(($url != '')&&(preg_match('/^http:\/\/instagr\.am\/p\/[a-zA-Z0-9-_]+\/$/', $url))) {
		switch($size) {
			case 'large':
				$maxwidth = 612;
				break;
			case 'small':
				$maxwidth = 150;
				break;
			case 'middle':
			default:
				$maxwidth = 306;
				break;
		}
		$oembed_url = "http://instagr.am/api/v1/oembed/?url=".rawurlencode($url)."&maxwidth=".$maxwidth;
		$ch = curl_init($oembed_url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Instagram 1.12.1 (iPhone; iPhone OS 4.2.1; lv_LV)");
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpcode >= 200 && $httpcode < 400){
			$data = json_decode($data);
			if($data->url){
				$html = '<img src="'.$data->url.'"'.($data->title!=''?' alt="'.$data->title.'"':'').' width="'.$maxwidth.'" height="'.$maxwidth.'" />';
				if($addlink == 'yes') {
					return '<a href="'.$url.'"'.($data->title!=''?' title="'.$data->title.'"':'').'>'.$html.'</a>';
				} else return $html;
			}
		}
	}
	return null;
}

add_action( 'widgets_init', 'load_wpinstagram' );
function load_wpinstagram() {
	register_widget( 'WPInstagram_Widget' );
}
class WPInstagram_Widget extends WP_Widget {
	function WPInstagram_Widget(){

		$widget_ops = array( 'classname' => 'wpinstagram', 'description' => __('Displays latest 20 instagrams.', 'wpinstagram') );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'wpinstagram-widget' );
		$this->WP_Widget( 'wpinstagram-widget', __('Instagram', 'wpinstagram'), $widget_ops, $control_ops );
		
		if( is_active_widget( '', '', 'wpinstagram-widget' ) ) {
			$this->wpinstagram_path = get_bloginfo('wpurl').'/'.Str_Replace("\\", '/', SubStr(RealPath(DirName(__FILE__)), Strlen(ABSPATH)));
			wp_enqueue_script("jquery");
			wp_enqueue_script("fancybox", $this->wpinstagram_path."/js/jquery.fancybox-1.3.4.pack.js", Array('jquery'), '1.3.4');
			wp_enqueue_script("jquery.cycle", $this->wpinstagram_path."/js/jquery.cycle-2.94.min.js", Array('jquery'), '2.94');
			wp_enqueue_script("jquery.easing", $this->wpinstagram_path."/js/jquery.easing-1.3.pack.js", Array('jquery'), '1.3');
			wp_enqueue_script("jquery.mousewhell", $this->wpinstagram_path."/js/jquery.mousewheel-3.0.4.pack.js", Array('jquery'), '3.0.4');
			wp_enqueue_script("wpinstagram", $this->wpinstagram_path."/js/wpinstagram.js", Array('jquery', 'fancybox'));
			wp_enqueue_style("fancybox-css", $this->wpinstagram_path."/js/fancybox/jquery.fancybox-1.3.4.css", Array(), '1.3.4');
		}
		
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$id = $instance['id'];
		if($id){
			$images = wp_cache_get('instagrams', 'wpinstagram_cache');
			if(false == $images) {
				$images = $this->instagram_get_latest($instance);
				wp_cache_set('instagrams', $images, 'wpinstagram_cache', 3600);
			}
			if(!empty($images)){
				echo $before_widget;
				if($title){
					echo $before_title.$title.$after_title;
				}
				echo '<div class="wpinstagram">';
				foreach($images as $image){
					echo '<a href="'.$image['image_large'].'" title="'.$image['title'].'" rel="wpinstagram">'
						.'<img src="'.$image["image_small"].'" alt="'.$image['title'].'" width="150" height="150" />'
						.'</a>';
				}
				echo '</div>';
				echo $after_widget;
			}
		}
	}
	
	function instagram_get_pk($url){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Instagram 1.12.1 (iPhone; iPhone OS 4.2.1; lv_LV)");
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpcode >= 200 && $httpcode < 400){
		    $pattern = "/\/profiles\/profile_([0-9]+)_/i";
		    preg_match($pattern, $data, $matches);
		    if(isset($matches[1]) && intval($matches[1])){
		    	return $matches[1];
		    } else return new WP_Error('couldnotgetid', __("Sorry, couldn't get Your instagram ID! Try another instagram url!"));
		} else return new WP_Error('couldnotgetid', __("Sorry, couldn't access given url! Try another instagram url"));
	}
	
	
	function instagram_get_latest($instance){
		$images = array();
		if(intval($instance['id'])){
			$ch = curl_init("http://instagr.am/api/v1/feed/user/".$instance['id']);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, "Instagram 1.12.1 (iPhone; iPhone OS 4.2.1; lv_LV)");
			$data = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if($httpcode >= 200 && $httpcode < 400){
				$data = json_decode($data);
				if($data->status == "ok"){
					foreach($data->items as $item){
						$images[] = array(
							"title" => (isset($item->comments[0])?$item->comments[0]->text:""),
							"image_large" => $item->image_versions[0]->url,
							"image_middle" => $item->image_versions[1]->url,
							"image_small" => $item->image_versions[2]->url
						);
					}
				}
			}
		}
		return $images;
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['id'] = strip_tags( $new_instance['id'] );
		if($new_instance['singleinstagram'] != ""){
			$idcheck = $this->instagram_get_pk($new_instance['singleinstagram']);
			if(is_wp_error($idcheck)){
				echo $idcheck->get_error_message();
				return $old_instance;
			} else {
				$instance['id'] = $idcheck;
			}
		}
		return $instance;
	}
	
	function form( $instance ) {
		$defaults = array( 'title' => __('My instagrams', 'wpinstagram'), 'id' => __('', 'wpinstagram') );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'id' ); ?>"><?php _e('Instagram ID:', 'wpinstagram'); ?></label>
			<input id="<?php echo $this->get_field_id( 'id' ); ?>" name="<?php echo $this->get_field_name( 'id' ); ?>" value="<?php echo $instance['id']; ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'singleinstagram' ); ?>"><?php _e('To get Instagram ID, enter url of one of Your instagrams:', 'wpinstagram'); ?></label>
			<input id="<?php echo $this->get_field_id( 'singleinstagram' ); ?>" name="<?php echo $this->get_field_name( 'singleinstagram' ); ?>" value="<?php echo $instance['singleinstagram']; ?>" class="widefat" placeholder="http://instagr.am/p/../" />
		</p>
	<?php
	}
}
?>
