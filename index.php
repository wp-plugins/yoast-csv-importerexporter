<?php 
/*
Plugin Name: WordPress SEO by Yoast CSV Importer/Exporter
Plugin URI: http://twitter.com/freddielore
Description: Mass-update your site's SEO meta data in seconds by importing a comma-delimited CSV file. CSV export also supported.
Version: 1.1
Author: Freddie Lore
Author URI: http://twitter.com/freddielore
License: GPL2
*/

session_start();
$session_id='1'; //$session id
require_once('lib/parsecsv.lib.php');

if (!class_exists('FL_Yoast_CSV')) {
 
	class FL_Yoast_CSV {

		public function __construct() {

			// Add settings link on plugin page
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this,'fl_yoast_csv_settings_link' ));

			// create custom plugin settings menu
			add_action('admin_menu', array(&$this, 'fl_yoast_csv_tweaks'));
			add_action( 'admin_enqueue_scripts', array(&$this, 'fl_yoast_csv_enqueue_custom_scripts_css' ));
			add_action( 'wp_ajax_fl_csv_import', array(&$this, 'fl_yoast_ajax_csv_import'));
			add_action( 'wp_ajax_fl_csv_export', array(&$this, 'fl_yoast_ajax_csv_export'));
		}

		function fl_yoast_csv_settings_link($links) { 
			$settings_link = '<a href="options-general.php?page=fl-yoast-csv&tab=csv_import">Settings</a>'; 
		  	array_unshift($links, $settings_link); 
		  	return $links; 
		}

		function fl_yoast_csv_tweaks() {

			//create new top-level menu
			add_submenu_page('options-general.php', 'CSV Import/Export', 'CSV Import/Export', 'administrator', 'fl-yoast-csv', array(&$this, 'fl_yoast_csv_settings_page'));

			//call register settings function
			add_action( 'admin_init', array(&$this, 'fl_yoast_csv_settings' ));
		}

		function fl_yoast_csv_settings() {
			//register our settings
			register_setting( 'fl-yoast-csv-settings-group', 'fl_yoast_csv_favicon' );
		}

		function fl_yoast_csv_settings_page() { ?>
			<div class="wrap fl_yoast_csv_settings_page">
				
				<h2>WordPress SEO by Yoast - CSV Import/Export</h2>

				<?php 
				$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'csv_import';
				?>

				<h2 class="nav-tab-wrapper">
				    <a href="?page=fl-yoast-csv&tab=csv_import" class="nav-tab <?php echo ($active_tab == 'csv_import') ? 'nav-tab-active' : ''; ?>">Import</a>
				    <a href="?page=fl-yoast-csv&tab=csv_export" class="nav-tab <?php echo ($active_tab == 'csv_export') ? 'nav-tab-active' : ''; ?>">Export</a>
				</h2>

				<?php if( $active_tab == 'csv_import' ) { ?>

					<form id="imageform" method="post" enctype="multipart/form-data" action='<?php echo plugin_dir_url(__FILE__); ?>upload.php'>
						
						<div id="plupload-upload-ui" class="hide-if-no-js drag-drop">
							<div class="update-nag"><strong>IMPORTANT:</strong> Be sure to create a full database backup of your site before you begin the import process. The <em>Import</em> function works by updating each and every post meta entry created by <em>WordPress SEO by Yoast</em>.</div>
							<p>Upload your CSV File below</p> 

							<div id="drag-drop-area" style="position: relative;">
								<div class="drag-drop-inside" style="margin-top: 85px;">
									<p class="drag-drop-buttons" id="csvloadbutton">
										<input type="file" name="fl-yoast-csv" id="fl-yoast-csv" style="position: relative; z-index: 1;" />
										<?php $upload_dir = wp_upload_dir(); ?>
										<input type="hidden" name="upload_dir" value="<?php echo $upload_dir['basedir']; ?>">
									</p>
								</div>
							</div>
						</div>
					</form>

					<div id='csv_upload_result' style="margin-top: 15px;"></div>
				
				<?php 
				}
				else{ ?>

					<p>When you click the Export button below, a comma-delimited CSV file will be created for you to save to your computer.</p>

					<p>This CSV file will contain SEO meta data for your posts, pages and other custom post types you specify below. Once you’ve saved the CSV file, you can use the <code>Import</code> function in another WordPress site with both <a href="#">WordPress SEO by Yoast</a> and <a href="#">WordPress SEO by Yoast - CSV Import/Export</a> installed.</p>

					<h3>Choose what to export <a class='tooltip' href='#' onclick='return false;' rel='tooltip' title='Only <strong>WordPress SEO by Yoast</strong> data will be exported.'>&nbsp;</a></h3>

					<form method="POST" id="yoast-csv-export" action='<?php echo plugin_dir_url(__FILE__); ?>ajax.php'>
						<p><label><input type="radio" name="post_type" value="all" checked="checked"> All content</label></p>
						
						<p class="description">This will contain all SEO meta data of your posts, pages, and custom posts.</p>


						<p><label><input type="radio" name="post_type" value="post"> Post</label></p>
						<p><label><input type="radio" name="post_type" value="page"> Page</label></p>
						<?php 
						$args = array(
						   'public'   => true,
						   '_builtin' => false
						);

						$output = 'names'; // names or objects, note names is the default
						$operator = 'and'; // 'and' or 'or'

						$post_types = get_post_types( $args, $output, $operator );

						foreach ( $post_types  as $post_type ) {
						   echo '<p><label><input type="radio" name="post_type" value="' . $post_type . '"> ' . ucfirst($post_type) . '</label></p>';
						}
						?>

						<p class="submit"><input type="submit" name="submit" id="start-export" class="button button-primary" value="Export to CSV" data-url="<?php echo plugin_dir_url(__FILE__); ?>ajax.php"></p>
						<input type="hidden" name="action" value="fl_csv_export">
						<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'csv-export-nonce' ); ?>">

					</form>

				<?php } ?>
				
			</div>
		<?php 
		}

		function fl_yoast_csv_enqueue_custom_scripts_css($hook) {
		    wp_register_style( 'custom_fl_yoast_csv_wp_admin_css', plugin_dir_url( __FILE__ ) . 'css/admin-style.css' );
		    wp_enqueue_style( 'custom_fl_yoast_csv_wp_admin_css' );

		    wp_register_script( 'custom_fl_yoast_csv_wp_admin_wallform_js', plugin_dir_url( __FILE__ ) . 'js/jquery.csvUploader.js' );
		    wp_enqueue_script( 'custom_fl_yoast_csv_wp_admin_wallform_js' );

		    wp_enqueue_script( 'jquery-ui-draggable' );
		    wp_enqueue_script( 'jquery-ui-droppable' );

			wp_register_script( 'custom_fl_yoast_csv_wp_admin_js', plugin_dir_url( __FILE__ ) . 'js/admin-js.js' );
			wp_localize_script( 'custom_fl_yoast_csv_wp_admin_js', '_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ))); 
		    wp_enqueue_script( 'custom_fl_yoast_csv_wp_admin_js' );
		}

		function fl_yoast_ajax_csv_import(){

			if( $_POST['csv_file'] and $_POST['action'] and $_POST['action'] == 'fl_csv_import' ){

				$csv = new parseCSV( $_POST['csv_file'] );
				$count = 0;

			    $id_alias = $this->remove_brackets( $_POST['id_alias'] );
			    $title_alias = $this->remove_brackets( $_POST['title_alias'] );
			    $description_alias = $this->remove_brackets( $_POST['description_alias'] );
			    $keywords_alias = $this->remove_brackets( $_POST['keywords_alias'] );

			    $log_arr = array();
			    
			    if( $_POST['id_alias'] ){
			        
			        foreach($csv->data as $item) {

			            $count++;

			            // using ID and Post Type as unique identifier
			            if( $item[ $post_type_alias ] ){
			                if( get_post_type( $item[$id_alias] ) == $item[ $post_type_alias ] ){
			                    // update the meta title
			                    update_post_meta( $item[$id_alias], '_yoast_wpseo_title', $item[$title_alias]);
			                    
			                    // update the meta description
			                    update_post_meta( $item[$id_alias], '_yoast_wpseo_metadesc', $item[$description_alias]);

			                    // update the meta keyword
			                    update_post_meta( $item[$id_alias], '_yoast_wpseo_focuskw', $item[$keywords_alias]);

			                }
			            }
			            // using ID as unique key
			            else{
			                // update the meta title
			                update_post_meta( $item[$id_alias], '_yoast_wpseo_title', $item[$title_alias]);
			                
			                // update the meta description
			                update_post_meta( $item[$id_alias], '_yoast_wpseo_metadesc', $item[$description_alias]);

			                // update the meta keyword
			                update_post_meta( $item[$id_alias], '_yoast_wpseo_focuskw', $item[$keywords_alias]);
			            }
			            
			            $log_entry = array( 'id' => $item[$id_alias], 'title' => get_the_title( $item[$id_alias] ), 'permalink' => get_permalink( $item[$id_alias] ) ); 

			            array_push( $log_arr, $log_entry );
			        }
		    	}
		    }

		    $import_status = ( $_POST['records'] == $count ) ? 'Success' : 'Failed';
		    
		    echo json_encode( array( 'import_status' => $import_status, 'logs' => $log_arr ) );
			wp_die();

		}

		function fl_yoast_ajax_csv_export(){

			if( $_GET['post_type'] and $_GET['action'] and $_GET['action'] == 'fl_csv_export' ){
				if ( !wp_verify_nonce( $_GET['_wpnonce'], "csv-export-nonce")) {
			      	echo "Failed";
			   	}
			   	else{

			   		global $wpdb;
					$posttype = '';
					if( $_GET['post_type'] == 'all' ){
						
						$posttype = "$wpdb->posts.post_type = 'page' OR $wpdb->posts.post_type = 'post'";

						$args = array(
						   'public'   => true,
						   '_builtin' => false
						);

						$out = 'names'; 
						$operator = 'and'; 

						$post_types = get_post_types( $args, $out, $operator );

						foreach ( $post_types  as $post_type ) {
						   	$posttype .= " OR $wpdb->posts.post_type = '{$post_type}'";
						}
						wp_reset_query();
					}
					else{
						$posttype = "$wpdb->posts.post_type = '" . $_GET['post_type'] . "'";
					}

					$posts = $wpdb->get_results("
					SELECT (SELECT `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = '_yoast_wpseo_focuskw' AND post_id = $wpdb->posts.ID)  AS focuskw,
					(SELECT `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = '_yoast_wpseo_title' AND post_id = $wpdb->posts.ID)  AS title,
					(SELECT `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = '_yoast_wpseo_metadesc' AND post_id = $wpdb->posts.ID)  AS metadesc,
					$wpdb->posts.ID, $wpdb->posts.post_type, $wpdb->posts.post_title FROM `$wpdb->posts`
					WHERE $wpdb->posts.post_status = 'publish'
					AND (SELECT `meta_value` FROM `$wpdb->postmeta` WHERE `meta_key` = '_yoast_wpseo_meta-robots-noindex' AND post_id = $wpdb->posts.ID)is null 
					AND ($posttype)
					");
					 
					$temp = array();
					foreach($posts as $post) {
					    $post->permalink = get_permalink($post->ID);
						$temp[] = $post;
					}

			   		// admin-ajax.php was not designed for file downloads. Return download URL instead
			   		$_SESSION['fl_temp_data'] = $temp;
					echo plugin_dir_url(__FILE__) . "export.php?" . http_build_query($_GET);
				}
			}

			wp_die();

		}

		function remove_brackets($str){
		    $str = str_replace('{', '', $str);
		    $str = str_replace('}', '', $str);

		    return $str;
		}

	}
}

new FL_Yoast_CSV();

?>