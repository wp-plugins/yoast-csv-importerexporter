<?php 
session_start();
if( $_GET['post_type'] and $_GET['action'] and $_GET['action'] == 'fl_csv_export' ){
	header('HTTP/1.1 200 OK');
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="yoast_meta_data.csv"');
 	
 	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');

	// output the column headings
	fputcsv( $output, array('post_id', 'post_type', 'title', 'permalink', 'keyword', 'seo_meta_title', 'description' ) );
		
	foreach($_SESSION['fl_temp_data'] as $post) {
		fputcsv( $output, array( $post->ID, $post->post_type, $post->post_title, $post->permalink, $post->focuskw, $post->title, $post->metadesc ) );
	}
	// unset immediately
	unset( $_SESSION['fl_temp_data'] );
	
	fclose($output);
}

?>