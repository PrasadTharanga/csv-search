<?php
/**
 * Plugin Name:       CSV Search
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Search and display content of a CSV file. 
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Prasad Tharanga
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       my-basics-plugin
 * Domain Path:       /languages
 */
 
 add_action('wp_enqueue_scripts', 'enqueue_jquery_form');
 function enqueue_jquery_form(){
	wp_enqueue_script('jquery-form');
 }
 
 add_shortcode( 'displaysearch', 'display_search_function' );
 
 function display_search_function($atts, $content ){
	 
	 $output  = '
		<form id="myForm" action="'. admin_url('admin-ajax.php') .'" method="post" encript="multipart/form-data"> 
			<input type="text" name="searchtext" /> <br>
			<input type="hidden" name="action" value="submitdata">
			<input type="hidden" name="scid" value="'.$atts['id'].'">
			<input type="submit" value="Search" /> 
		</form>

		<div id="searchresult"></div>

		<script type="text/javascript">
			jQuery(document).ready(function($){
				$("#myForm").ajaxForm({
					success: function(response){
						// 
							console.log(response);
							if(response.success){
								//console.log(response.data.csvline["Student RegNo"]);
								// $("#searchresult").text(response.data.rowwithheaders);
								$("#searchresult").html(makeTable(response.data.headingLine, response.data.row));
							}else{
								$("#searchresult").html("<span>No record on database...<span>");
							}
						// 
					},
					error : function(response){
						console.log(response);
					},
					uploadProgress(event, position, total, persentComplete){
						console.log(persentComplete);
					},
					resetForm : false
				});
			});

			function makeTable(headingLine, row){
				output = "<table class=\"table\"><tr>";
				headingLine.forEach(function(value, key, arr){
					output = output + "<th>"+ value  + "</th>";
				});
				output = output + "</tr><tr>";
				row.forEach(function(value, key, arr){
					output = output + "<td>"+ value  + "</td>";
				});
				output = output + "</tr></table>";
				return output;
			}
 		</script>
 

	 ';

	 return $output;
 }

 

//  add_action('wp_ajax_submitdata', 'send_ajax_response_function');

add_action('wp_ajax_nopriv_submitdata', 'send_ajax_response_function');
add_action('wp_ajax_submitdata', 'send_ajax_response_function');

 

//  function send_ajax_response_function(){
// 	// wp_send_json_success($_POST);
// 	wp_send_json_success(['POST' => $_POST, 'FILES' => $_FILES]);
//  }

//  http://wp6.test/csv/wp-content/uploads/2022/07/sample1.csv
 
function send_ajax_response_function(){
	// wp_send_json_success($_POST);
	// wp_send_json_success(['POST' => $_POST, 'FILES' => $_FILES]);

	$searchtext =  $_POST['searchtext'];
	$scid =  $_POST['scid'];

	require_once('shortcodelist.php');
	$shortCodeDetails = $scl[$scid];
	
	// $fileURL = 'http://wp6.test/csv/wp-content/uploads/2022/07/sample1.csv';
	$fileURL = $shortCodeDetails['url'];
	
	$file = fopen($fileURL,"r");

	$headingLine = fgetcsv($file);
	while(! feof($file))
	{
		$line = fgetcsv($file);
		$checkColVal = $line[$shortCodeDetails['searchColNo']];
		if($searchtext ==  $checkColVal){
			wp_send_json_success(['POST' => $_POST, 'headingLine' => $headingLine, 'row' => $line, 'rowwithheaders' => mergeTwoArray($headingLine, $line)]);
			// wp_send_json_success(['POST' => $_POST, 'csvline' => $line[0]]);
			break;		
		}
		// wp_send_json_success(['POST' => $_POST, 'csvline' => $line]);
	}
	fclose($file);
 
	wp_send_json_error(['POST' => $_POST, 'error_message' => 'No data ........']);
}

function mergeTwoArray($headingLine, $line){
	$newArray = [];
	foreach($headingLine as $k=>$v){
		$newArray[$v] = $line[$k];
	}
	return $newArray;
}


add_action('admin_menu', 'csv_search_admin_page');
 
function csv_search_admin_page(){
    add_menu_page( 'CSV Search', 'CSV Search', 'manage_options', 'csv-search', 'csv_search_adminpage_init' );
}
 
function csv_search_adminpage_init(){
    echo "<h1>CSV Search - ShortCodes</h1><hr>";
}