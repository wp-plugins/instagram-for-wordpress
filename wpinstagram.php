<?php
/*
	Plugin Name: Instagram for Wordpress
	Plugin URI: http://wordpress.org/extend/plugins/instagram-for-wordpress/
	Description: Comprehensive Instagram sidebar widget with many options.
	Version: 2.0.4
	Author: jbenders
	Author URI: http://ink361.com/
*/

if(!defined('INSTAGRAM_PLUGIN_URL')) {
  define('INSTAGRAM_PLUGIN_URL', plugins_url() . '/' . basename(dirname(__FILE__)));
}

function wpinstagram_admin_register_head() {
    $siteurl = get_option('siteurl');       
}

add_action('admin_head', 'wpinstagram_admin_register_head');
add_action('widgets_init', 'load_wpinstagram');
add_option('instagram-widget-cache', null, false, false);
add_option('wpaccount', null, false, false);
add_action('admin_notices', 'wpinstagram_show_instructions');

function load_wpinstagram() {
	register_widget('WPInstagram_Widget');
}

function wpinstagram_show_instructions() {
	global $wpdb;
	
	$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM igwidget_widget"));
	
	if (sizeof($results) == 0) {	
		$url = plugins_url('wpinstagram-admin.css', __FILE__); 
		wp_enqueue_style('wpinstagram-admin.css', $url);
		wp_enqueue_script("jquery");
		wp_enqueue_script("lightbox", plugin_dir_url(__FILE__)."js/lightbox.js", Array('jquery'), null);
		
		require(plugin_dir_path(__FILE__) . 'templates/setupInstructions.php');		
	}
}

function load_wpinstagram_footer(){
	?>
	<script>
	        jQuery(document).ready(function($) {
	        	try {
		                $("ul.wpinstagram").find("a").each(function(i, e) {
		                       	e = $(e);
        	        	        e.attr('data-href', e.attr('href'));
        		                e.attr('href', e.attr('data-original'));
		                });

        	        	$("ul.wpinstagram.live").find("a.mainI").fancybox({
	        	                "transitionIn":                 "elastic",
	                        	"transitionOut":                "elastic",
                        		"easingIn":                     "easeOutBack",
                		        "easingOut":                    "easeInBack",
        		                "titlePosition":                "over",   
		                        "padding":                              0,
                        		"hideOnContentClick":   "false",
                		        "type":                                 "image",   
        		                titleFormat:                    function(x, y, z) {
        		                        var html = '<div id="fancybox-title-over">';
	
	                	                if (x && x.length > 0) {  
	                	                	x = x.replace(/^#([0-9a-zA-Z\u4E00-\u9FA5\-_]+)/g, '<a href="http://ink361.com/app/tag/$1" alt="View Instagram tag #$1" title="View Instagram tag #$1" target="_blank">#$1</a>');
	                	                	x = x.replace(/[ ]#([0-9a-zA-Z\u4E00-\u9FA5\-_]+)/g, ' <a href="http://ink361.com/app/tag/$1" alt="View Instagram tag #$1" title="View Instagram tag #$1" target="_blank">#$1</a>');
	                	                
        	                        	        html += x + ' - ';
	                        	        }

                        	        	html += '<a href="http://ink361.com" target="_blank" alt="INK361 Instagram web viewer" title="INK361 Instagram web viewer">INK361 Instagram web viewer</a></div>';
                	        	        return html;
        	        	        }
	        	        });

		                jQuery('#fancybox-content').live('click', function(x) {
        	                	var src = $(this).find('img').attr('src');
	                	        var a = $("ul.wpinstagram.live").find('a.[href="' + src + '"]').attr('data-user-url');                  		
                		        document.getElementById('igTracker').src=$('ul.wpinstagram').find('a[href="' + src + '"]').attr('data-onclick');
        		                window.open(a, '_blank');
		                });
			} catch(error) {
				$("ul.wpinstagram").find("a").each(function(i, e) {
		                       	e = $(e);
        	        	        e.attr('href', e.attr('data-href'));
        	        	        e.attr('target', '_blank');
				});
			}
        	});
	</script>
	<?php
}

class WPInstagram_Widget extends WP_Widget {
	function WPInstagram_Widget($args=array()){
	        $width = '220';
                $height = '220';
        
                $widget_ops = array('description' => __('Displays Instagrams', 'wpinstagram'));
                $control_ops = array('id_base' => 'wpinstagram-widget');        
		
                $this->wpinstagram_path = plugin_dir_url( __FILE__);
                $this->WP_Widget('wpinstagram-widget', __('Instagram Widget', 'wpinstagram'), $widget_ops, $control_ops);
                
                $withfancybox = false;
                if(in_array('fancybox-for-wordpress/fancybox.php',(array)get_option($this->id . 'active_plugins',array()))==false) {
                        $withfancybox = true;
                }
                
                if (is_admin()) {
	                $this->handleTables();
				}

                if (is_active_widget('', '', 'wpinstagram-widget') && !is_admin()) {            
                        wp_enqueue_script("jquery");
                        wp_enqueue_script("jquery.easing", $this->wpinstagram_path."js/jquery.easing-1.3.pack.js", Array('jquery'), null);
                        wp_enqueue_script("jquery.cycle", $this->wpinstagram_path."js/jquery.cycle.all.js", Array('jquery'), null);
                        wp_enqueue_style('wpinstagram', $this->wpinstagram_path . 'wpinstagram.css', Array(), '0.5');
                        if ($withfancybox) {
                                wp_enqueue_script("fancybox", $this->wpinstagram_path."js/jquery.fancybox-1.3.4.pack.js", Array('jquery'), null);
                                wp_enqueue_style("fancybox-css", $this->wpinstagram_path."js/fancybox/jquery.fancybox-1.3.4.min.css", Array(), null);
                                wp_enqueue_script("jquery.mousewhell", $this->wpinstagram_path."js/jquery.mousewheel-3.0.4.pack.js", Array('jquery'), null);
                                add_action('wp_footer', 'load_wpinstagram_footer');
                        }
                }
	}
		
	function widget($args, $instance) {
		extract($args);		

		if ($instance['db_id']) {
			global $wpdb;
	
			$details = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $this->_tablePrefix() . "widget WHERE uid=%s", $instance['db_id']));		

			if (sizeof($details) > 0) {
				$details = $details[0];
				
				$details->settings = unserialize($details->settings);
				
				if (is_bool($details->settings)) {
					$details->settings = array();
				}
				
				#ensure we have all of our values
				$details->settings = $this->_confirmDefaults($details->settings);								
				
				#is our cache valid
				if ($details->cache_time !== NULL && $details->cache_time !== '') {				
					$time = DateTime::createFromFormat('Y-m-d H:i:s', $details->cache_time);
					
					#need to get it from the DB as it may run on a different timezone setting
					$fromDB = $wpdb->get_results("SELECT NOW() as dbtime");
					
					$now = DateTime::createFromFormat('Y-m-d H:i:s', $fromDB[0]->dbtime);
					
					$interval = new DateInterval('PT' . $this->_defaultCacheTime() . 'S');
					
					if ($details->cache_timeout !== NULL && $details->cache_timeout !== '') {
						$interval = new DateInterval('PT' . $details->cache_timeout . 'S');
					}

					if ($time->add($interval) > $now) {
						#cache time is valid, confirm cached value matches what we expect
						if ($details->result_cache !== NULL && $details->result_cache !== '') {
							$cached = json_decode($details->result_cache, true);
							
							if (is_array($cached) && sizeof($cached) > 0) {
								#should be ok, we can always add more checks later
								$this->_display_results($cached, $details, true);
								return;
							}														
						}
					}
				}
		
				if ($details->token && $details->token !== '') {
					if ($details->settings['display'] == 'self') {
						$this->_display_user('self', $details);					
					} else if ($details->settings['display'] == 'likes') {
						$this->_display_likes($details);
					} else if ($details->settings['display'] == 'feed') {
						$this->_display_feed($details);
					} else if ($details->settings['display'] == 'popular') {
						$this->_display_popular($details);					
					} else if ($details->settings['display'] == 'user') {
						$this->_display_user($details->settings['user'], $details);
					} else if ($details->settings['display'] == 'tags') {
						$this->_display_tags($details->settings['tag1'], 
								     $details->settings['tag2'], 
								     $details->settings['tag3'], 
								     $details->settings['tag4'], 
									 $details->settings['tagCompare'],
								     $details);
					}
				}				
			}
		}
    }
	
	function form($instance) {		
		$url = plugins_url('wpinstagram-admin.css', __FILE__); 
		wp_enqueue_style('wpinstagram-admin.css', $url);
		wp_enqueue_script("jquery");
		wp_enqueue_script("lightbox", plugin_dir_url(__FILE__)."js/lightbox.js", Array('jquery'), null);

		$details = NULL;

		if ($instance['db_id']) {
			global $wpdb;
	
			$details = $wpdb->get_results($wpdb->prepare("SELECT * FROM " . $this->_tablePrefix() . "widget WHERE uid=%s", $instance['db_id']));		
			
			if (sizeof($details) > 0) {
				$details = $details[0];
				
				$details->settings = unserialize($details->settings);
				
				if (is_bool($details->settings)) {
					$details->settings = array();
				}
				
				#ensure we have all of our values
				$details->settings = $this->_confirmDefaults($details->settings);
				
				if ($details->error_detected > 0) {
					require(plugin_dir_path(__FILE__) . 'templates/errorBackend.php');
				}
			}
		}

		require(plugin_dir_path(__FILE__) . 'templates/setupButton.php');
		
		return;
	}
	
	function update($new_instance, $old_instance){
		$instance = $new_instance;
		global $wpdb;
		
		#new version
		if (!$old_instance['db_id']) {
			#create new uid in db
			error_log("Creating new db_id");
			
			$wpdb->get_results($wpdb->prepare("INSERT INTO " . $this->_tablePrefix() . "widget (localid, setup, last_modified, cache_timeout) VALUES (%s, 0, NOW(), %s)", $this->id, $this->_defaultCacheTime()));
			$result = $wpdb->get_results("SELECT last_insert_id() as uid");
						
			$instance['db_id'] = $result[0]->uid;
		} else {
			$instance['db_id'] = $old_instance['db_id'];
		}
		
		if ($_POST['instance_token']) {
			$wpdb->get_results($wpdb->prepare("UPDATE " . $this->_tablePrefix() . "widget SET error_detected=0, setup=1, token=%s, last_modified=NOW() WHERE uid=%s", $_POST['instance_token'], $instance['db_id']));
		}
		
		#compile our settings if we have them
		if ($_POST['title']) { #we always have our title
			$settings = array(
				"title"		=> stripslashes($_POST['title']),
				"user"		=> $_POST['user'],
				"username" 	=> stripslashes($_POST['username']),
				"tag1"		=> stripslashes($_POST['tag1']),
				"tag2"		=> stripslashes($_POST['tag2']),
				"tag3"		=> stripslashes($_POST['tag3']),
				"tag4"		=> stripslashes($_POST['tag4']),
				"tagCompare"=> stripslashes($_POST['tagCompare']),
				"width"		=> stripslashes($_POST['width']),
				"height"	=> stripslashes($_POST['height']),
				"delay"		=> stripslashes($_POST['delay']),
				"display"	=> $_POST['display'],
				"method"	=> $_POST['method'],
				"cols"		=> $_POST['cols'],
				"rows"		=> $_POST['rows'],
				"transition"	=> $_POST['transition'],
				"responsive"	=> $_POST['responsive'],
				"sharing"	=> $_POST['sharing'],
				"verbose"	=> $_POST['verbose'],
			);
			
			$cacheTimeout = NULL;
			if ($_POST['cache_duration']) {
				$cacheTimeout = $_POST['cache_duration'];
			}
			
			$wpdb->get_results($wpdb->prepare("UPDATE " . $this->_tablePrefix() . "widget SET settings=%s, cache_timeout=%s, cache_time=NULL, last_modified=NOW() WHERE uid=%s", serialize($settings), $cacheTimeout, $instance['db_id']));
		}
		
		return $instance;
	}
        
	function _display_popular($settings) {
		$images = array();

		if ($settings->token) {
			$url = "https://api.instagram.com/v1/media/popular?count=50&access_token=" . $settings->token;
			
			$response = wp_remote_get($url, array('sslverify' => apply_filters('https_local_ssl_verify', false)));
			if (!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200) {
				$data = json_decode($response['body'], true);
				if ($data['meta']['code'] == 200) {
					foreach ($data['data'] as $item) {
						if (isset($item['caption']['text'])) {
							$image_title = '@' . $item['user']['username'] . ': "' . filter_var($item['caption']['text'], FILTER_SANITIZE_STRING) . '"';
						} else if (!isset($item['caption']['text'])) {
							$image_title = "Instagram by @" . $item['user']['username'];
						}
						
						$images[] = array(
							"id"		=> $item['id'],
							"title"		=> $image_title,
							"parsedtitle"	=> $this->_parse_title($image_title),
							"user"		=> $item['user']['id'],
							"username"	=> $item['user']['username'],
							"image_small"	=> $item['images']['thumbnail']['url'],
							"image_middle"	=> $item['images']['low_resolution']['url'],
							"image_large"	=> $item['images']['standard_resolution']['url'],
						);
					}
				}							
			} else {
				$this->_handle_error_response($response, $settings);
				return;
			}			
		}
		
		return $this->_display_results($images, $settings, false);
	}
		
	function _display_feed($settings) {
		$images = array();

		if ($settings->token) {
			$url = "https://api.instagram.com/v1/users/self/feed?count=50&access_token=" . $settings->token;
			
			$response = wp_remote_get($url, array('sslverify' => apply_filters('https_local_ssl_verify', false)));
			if (!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200) {
				$data = json_decode($response['body'], true);
				if ($data['meta']['code'] == 200) {
					foreach ($data['data'] as $item) {
						if (isset($item['caption']['text'])) {
							$image_title = '@' . $item['user']['username'] . ': "' . filter_var($item['caption']['text'], FILTER_SANITIZE_STRING) . '"';
						} else if (!isset($item['caption']['text'])) {
							$image_title = "Instagram by @" . $item['user']['username'];
						}
						
						$images[] = array(
							"id"		=> $item['id'],
							"title"		=> $image_title,							
							"parsedtitle"	=> $this->_parse_title($image_title),
							"user"		=> $item['user']['id'],
							"username"	=> $item['user']['username'],
							"image_small"	=> $item['images']['thumbnail']['url'],
							"image_middle"	=> $item['images']['low_resolution']['url'],
							"image_large"	=> $item['images']['standard_resolution']['url'],
						);
					}
				}							
			} else {
				$this->_handle_error_response($response, $settings);
				return;
			}			
		}
		
		return $this->_display_results($images, $settings, false);
	}
	
	function _display_likes($settings) {
		$images = array();
		
		if ($settings->token) {
			$url = "https://api.instagram.com/v1/users/self/media/liked?count=50&access_token=" . $settings->token;
			
			$response = wp_remote_get($url, array('sslverify' => apply_filters('https_local_ssl_verify', false)));
			if (!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200) {
				$data = json_decode($response['body'], true);
				if ($data['meta']['code'] == 200) {
					foreach ($data['data'] as $item) {
						if (isset($item['caption']['text'])) {
							$image_title = '@' . $item['user']['username'] . ': "' . filter_var($item['caption']['text'], FILTER_SANITIZE_STRING) . '"';
						} else if (!isset($item['caption']['text'])) {
							$image_title = "Instagram by @" . $item['user']['username'];
						}
						
						$images[] = array(
							"id"		=> $item['id'],
							"title"		=> $image_title,
							"parsedtitle"	=> $this->_parse_title($image_title),
							"user"		=> $item['user']['id'],
							"username"	=> $item['user']['username'],
							"image_small"	=> $item['images']['thumbnail']['url'],
							"image_middle"	=> $item['images']['low_resolution']['url'],
							"image_large"	=> $item['images']['standard_resolution']['url'],
						);
					}
				}							
			} else {
				$this->_handle_error_response($response, $settings);
				return;
			}			
		}
		
		return $this->_display_results($images, $settings, false);
	}

	function _display_user($user, $settings) {
		$images = array();
		$user = str_replace("ig-", "", $user);
		
		if ($settings->token) {
			$url = "https://api.instagram.com/v1/users/" . $user . "/media/recent?count=50&access_token=" . $settings->token;
			
			$response = wp_remote_get($url, array('sslverify' => apply_filters('https_local_ssl_verify', false)));									
			if (!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200) {
				$data = json_decode($response['body'], true);
				if ($data['meta']['code'] == 200) {
					foreach ($data['data'] as $item) {
						if (isset($item['caption']['text'])) {
							$image_title = '@' . $item['user']['username'] . ': "' . filter_var($item['caption']['text'], FILTER_SANITIZE_STRING) . '"';
						} else if (!isset($item['caption']['text'])) {
							$image_title = "Instagram by @" . $item['user']['username'];
						}
						
						$images[] = array(
							"id"		=> $item['id'],
							"title"		=> $image_title,
							"parsedtitle"	=> $this->_parse_title($image_title),
							"user"		=> $item['user']['id'],
							"username"	=> $item['user']['username'],
							"image_small"	=> $item['images']['thumbnail']['url'],
							"image_middle"	=> $item['images']['low_resolution']['url'],
							"image_large"	=> $item['images']['standard_resolution']['url'],
						);
					}
				}							
			} else {
				$this->_handle_error_response($response, $settings);
				return;
			}
		}
		
		return $this->_display_results($images, $settings, false);
	}
	
	function _display_tags($tag1, $tag2, $tag3, $tag4, $compareMethod, $settings) {
		$images = array();
		
		$numRequired = 0;
		
		#BEHOLD MY EFFICIENT WAY OF GETTING MANY TAGS!
		if ($tag1 && $tag1 != '' && $tag1 != 'None') {
			$images += $this->_get_tagged_photos($tag1, $settings);
			$numRequired++;
		}
		if ($tag2 && $tag2 != '' && $tag2 != 'None') {
			$images += $this->_get_tagged_photos($tag2, $settings);
			$numRequired++;
		}
		if ($tag3 && $tag3 != '' && $tag3 != 'None') {
			$images += $this->_get_tagged_photos($tag3, $settings);
			$numRequired++;
		}
		if ($tag4 && $tag4 != '' && $tag4 != 'None') {
			$images += $this->_get_tagged_photos($tag4, $settings);
			$numRequired++;
		}
		
		#restrict if required
		if ($compareMethod === 'restrictive') {
			$new = array();
			
			foreach ($images as $image) {
				$numFound = 0;
				 				
				if (is_array($image['tags'])) {
					foreach ($image['tags'] as $imageTag) {						
						if ($tag1 && $tag1 !== '' && $tag1 !== 'None') {
							if ($imageTag === $tag1) {
								$numFound++;
							}
						}
						if ($tag2 && $tag2 !== '' && $tag2 !== 'None') {
							if ($imageTag === $tag2) {
								$numFound++;
							}
						}
						if ($tag3 && $tag3 !== '' && $tag3 !== 'None') {
							if ($imageTag === $tag3) {
								$numFound++;								
							}
						}
						if ($tag4 && $tag4 !== '' && $tag4 !== 'None') {
							if ($imageTag === $tag4) {
								$numFound++;
							}
						}												
					}
				}
				
				if ($numFound === $numRequired) {
					$new[] = $image;	
				}
			}
						
			$images = $new;
		}				
		
		#jumble them up
		shuffle($images);
		
		return $this->_display_results($images, $settings, false);
	}
	
	function _display_tag($tag, $settings) {		
		return $this->_display_results($this->_get_tagged_photos($tag, $settings), $settings, false);
	}
	
	function _get_tagged_photos($tag, $settings) {
		#tidy up our tag
		$tag = str_replace("#", "", $tag);
		$images = array();
		
		if ($settings->token) {
			$url = "https://api.instagram.com/v1/tags/" . $tag . "/media/recent?count=50&access_token=" . $settings->token;
			
			$response = wp_remote_get($url, array('sslverify' => apply_filters('https_local_ssl_verify', false)));
			if (!is_wp_error($response) && $response['response']['code'] < 400 && $response['response']['code'] >= 200) {
				$data = json_decode($response['body'], true);
				if ($data['meta']['code'] == 200) {
					foreach ($data['data'] as $item) {
						if (isset($item['caption']['text'])) {
							$image_title = '@' . $item['user']['username'] . ': "' . filter_var($item['caption']['text'], FILTER_SANITIZE_STRING) . '"';
						} else if (!isset($item['caption']['text'])) {
							$image_title = "Instagram by @" . $item['user']['username'];
						}
						
						$images[] = array(
							"id"		=> $item['id'],
							"title"		=> $image_title,
							"parsedtitle"	=> $this->_parse_title($image_title),
							"user"		=> $item['user']['id'],
							"username"	=> $item['user']['username'],
							"image_small"	=> $item['images']['thumbnail']['url'],
							"image_middle"	=> $item['images']['low_resolution']['url'],
							"image_large"	=> $item['images']['standard_resolution']['url'],
							"tags"			=> $item['tags'],
						);
					}
				}				
			} else {
				$this->_handle_error_response($response, $settings);
				return;
			}
		}	
		
		return $images;
	}
	
	function _handle_error_response($response, $settings) {
		$code = -9999;
		$error = "";
	
		if (is_wp_error($response)) {
			$error = "An unknown error occurred, please make sure that your Wordpress installation can access remote resources.";
		} else {
			if (array_key_exists('response', $response)) {
				if (array_key_exists('code', $response['response'])) {
			 		$code = $response['response']['code'];
				}
			}
			if (array_key_exists('meta', $response) && array_key_exists('error_message', $response['meta'])) {
				$error = $response['meta']['error_message'];
			}
			if (array_key_exists('error_message', $response)) {
				$error = $response['error_message'];
			}
		}

		#standard messages
		if ($code === 400) {
			$error = 'Your access token has expired! Please login to your administration to reset your token.';
			$code = 1;
		}
		
		if ($code === 429) {
			$error = 'You have reached your API request limit, consider adjusting your cache timeout value in your administration.';
			$code = 2;
		}
		
		$this->_display_error($error, $code, $settings);
	}
	
	function _display_error($error, $code, $settings) {
		if ($settings->settings['verbose'] === 'yes') {
			require(plugin_dir_path(__FILE__) . 'templates/errorFrontend.php');
			
			if ($code > 0) {
				#set the error detected flag
				global $wpdb;				
				$wpdb->get_results($wpdb->prepare("UPDATE " . $this->_tablePrefix() . "widget SET error_detected=%s WHERE uid=%s", $code, $settings->uid));
			}
		} else {
			#log it into our error log instead
			error_log($error);
		}
	}
	
	function _display_results($images, $settings, $fromCache) {
		#now lets cache our images		
		if (!$fromCache) {
			global $wpdb;
			
			$wpdb->get_results($wpdb->prepare("UPDATE " . $this->_tablePrefix() . "widget SET result_cache=%s, cache_time=NOW() WHERE uid=%s", json_encode($images), $settings->uid));
			
			if ($settings->error_detected == 2) {
				$wpdb->get_results($wpdb->prepare("UPDATE " . $this->_tablePrefix() . "widget SET error_detected=0 WHERE uid=%s", $settings->uid));
			}
		}
		
		if (!$settings->settings['method'] || $settings->settings['method'] == 'grid') {
			require(plugin_dir_path(__FILE__) . 'templates/grid.php');								
		} else if ($settings->settings['method'] == 'grid-page') {
			require(plugin_dir_path(__FILE__) . 'templates/gridPage.php');										
		} else if ($settings->settings['method'] == 'slideshow') {
			require(plugin_dir_path(__FILE__) . 'templates/slideshow.php');										
		}
		
		if (sizeof($images) > 0) {		
			return true;
		} else {
			return false;
		}
	}

        function _parse_title($title) {
        	$title = preg_replace('/#([0-9a-zA-Z\-_]+)/i', '<a href="http://ink361.com/app/tag/$1" alt="View Instagram tag $1" title="View Instagram tag $1" target="_blank">#$1</a>', $title);
        	$title = preg_replace('/[ ]#([0-9a-zA-Z\-_]+)/i', '<a href="http://ink361.com/app/tag/$1" alt="View Instagram tag $1" title="View Instagram tag $1" target="_blank">#$1</a>', $title);
        
        	return $title;
        }

	function _tablePrefix($args=array()) {
		extract($args);
	
		return 'igwidget_';
	}
	
	function _defaultCacheTime($args=array()) {
		extract($args);
		
		#5 minutes by default
		return 300;
	}

	function _tableDescription($args=array()) {
		extract($args);
		
		return array(
			$this->_tablePrefix() . 'widget' => array(
				'uid' 	=> array(
					'type' 	=> 'int(11)',
					'null' 	=> false,
					'pk' 	=> true,
					'auto'	=> true,
				),
				'localid' => array(
					'type' 	=> 'varchar(255)',
					'null' 	=> false,
				),
				'token'	=> array(
					'type' 	=> 'varchar(255)',
					'null'	=> true,
				),
				'setup' => array(
					'type'	=> 'int(1)',
					'null'	=> false,
				),
				'error_detected' => array(
					'type'	=> 'int(1)',
					'null'	=> true,
				),
				'settings' => array(
					'type'	=> 'text',
					'null'	=> true,
				),
				'last_modified' => array(
					'type' 	=> 'datetime',
					'null'	=> true,
				),
				'result_cache' => array(
					'type' 	=> 'mediumtext',
					'null'	=> true,
				),
				'cache_time' => array(
					'type' 	=> 'datetime',
					'null'	=> true,
				),
				'cache_timeout' => array(
					'type' 	=> 'int(9)',
					'null'	=> true,
				),
			),
		);
	}
	
	function _describeTable($name) {
		global $wpdb;
		
		$ret = array();		
		$result = $wpdb->get_results("DESC $name");
		
		if (sizeof($result) == 0) {
			return NULL;
		} else {
			foreach ($result as $column) {
				$fields = array();

				#type
				$fields['type'] = strtolower($column->Type);
				#null				
				if (strtolower($column->Null) === 'no') {
					$fields['null'] = false;
				} else {
					$fields['null'] = true;
				}
				#pk
				if (strtolower($column->Key) === 'pri') {
					$fields['pk'] = true;
				} else {
					$fields['pk'] = false;
				}
				#auto
				if (strtolower($column->Extra) === 'auto_increment') {
					$fields['auto'] = true;
				} else {
					$fields['auto'] = false;
				}

				$ret[$column->Field] = $fields;
			}
		}				
		
		return $ret;
	}
	
	function handleTables($args=array()) {
		global $wpdb;
		
		extract($args);
		
		$tables = $this->_tableDescription();
		
		foreach ($tables as $name => $description) {
			$currentTable = $this->_describeTable($name);		
			
			if (is_null($currentTable)) {
				#make the table!
				$query = "CREATE TABLE $name (";
				
				foreach ($description as $columnName => $columnDetails) {
					$query .= " $columnName ";
					if ($columnDetails['type']) {
						$query .= $columnDetails['type'] . ' ';
					} else {
						$query .= ' varchar(255) ';
					}
					
					if ($columnDetails['null']) {
						$query .= ' NULL ';
					} else {
						$query .= ' NOT NULL ';
					}
					
					if ($columnDetails['auto']) {
						$query .= ' auto_increment ';
					}
					
					if ($columnDetails['pk']) {
						$query .= ' primary key ';
					}
					
					$query .= ', ';
				}
				
				$query = substr($query, 0, -2);
				$query .= ")";
				$result = $wpdb->get_results($query);
			} else {
				#compare the columns to see if we need to add one
				foreach ($description as $columnName => $columnDetails) {
					$found = false;
					foreach ($currentTable as $currentName => $currentDetails) {
						if ($currentName === $columnName) {
							$found = true;
						}
					}
					
					if ($found === false) {
						$query = "ALTER TABLE $name ADD COLUMN ";
						
						$query .= " $columnName ";
						if ($columnDetails['type']) {
							$query .= $columnDetails['type'] . ' ';
						} else {
							$query .= ' varchar(255) ';
						}
					
						if ($columnDetails['null']) {
							$query .= ' NULL ';
						} else {
							$query .= ' NOT NULL ';
						}
					
						if ($columnDetails['auto']) {
							$query .= ' auto_increment ';
						}
						
						if ($columnDetails['pk']) {
							$query .= ' primary key ';
						}
						
						$result = $wpdb->get_results($query);
					}
				}
			}
		}
	}
	
	function _confirmDefaults($settings) {
		if (!array_key_exists("title", $settings) 	|| $settings['title'] === '') {
			$settings['title'] 	= 'My Instagrams';
		}
		
		if (!array_key_exists("user", $settings)) {
			$settings['user'] 	= '';
		}
		
		if (!array_key_exists("username", $settings)) {
			$settings['username'] 	= '';
		}
		
		if (!array_key_exists("tag1", $settings)) {
			$settings['tag1'] 	= '';
		}
		
		if (!array_key_exists("tag2", $settings)) {
			$settings['tag2']	= '';
		}

		if (!array_key_exists("tag3", $settings)) {
			$settings['tag3']	= '';
		}
		
		if (!array_key_exists("tag4", $settings)) {
			$settings['tag4']	= '';
		}
		
		if (!array_key_exists("tagCompare", $settings)) {
			$settings['tagCompare'] = 'cumulative';
		}
		
		if (!array_key_exists("width", $settings) 	|| $settings['width'] === '') {
			$settings['width'] 	= '220';
		}
		
		if (!array_key_exists("height", $settings) 	|| $settings['height'] === '') {
			$settings['height'] 	= '220';
		}
		
		if (!array_key_exists("delay", $settings) 	|| $settings['delay'] === '') {
			$settings['delay'] 	= '4';
		}
		
		if (!array_key_exists("display", $settings) 	|| $settings['display'] === '') {
			$settings['display'] 	= 'self';
		}
		
		if (!array_key_exists("method", $settings) 	|| $settings['method'] === '') {
			$settings['method'] 	= 'grid';
		}
		
		if (!array_key_exists("cols", $settings) 	|| $settings['cols'] === '') {
			$settings['cols'] 	= '3';
		}
		
		if (!array_key_exists("rows", $settings) 	|| $settings['rows'] === '') {
			$settings['rows'] 	= '3';
		}
		
		if (!array_key_exists("transition", $settings) 	|| $settings['transition'] === '') {
			$settings['transition'] = 'vert';
		}
		
		if (!array_key_exists("responsive", $settings) 	|| $settings['responsive'] === '') {
			$settings['responsive'] = 'yes';
		}
		
		if (!array_key_exists("sharing", $settings)	|| $settings['sharing'] === '') {
			$settings['sharing'] = 'yes';
		}
		
		if (!array_key_exists("verbose", $settings)	|| $settings['verbose'] === '') {
			$settings['verbose'] = 'yes';
		}
		
		return $settings;
	}
}
?>
