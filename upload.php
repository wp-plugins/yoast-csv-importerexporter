<?php 
require_once('lib/parsecsv.lib.php');
session_start();
$session_id='1'; //$session id

$valid_formats = array("csv");
if( isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST" and isset($_FILES)){

    $name = $_FILES['fl-yoast-csv']['name'];
    $size = $_FILES['fl-yoast-csv']['size'];
    $path = $_POST['upload_dir'];
    
    if(strlen($name)){
        
        $ext = getExtension($name);
        if(in_array($ext,$valid_formats)){
            if($size < (8192*8192) ){
                $actual_image_name = 'yoast-meta-data-' . time().substr(str_replace(" ", "_", $ext), 5).".".$ext;
                $tmp = $_FILES['fl-yoast-csv']['tmp_name'];
                if(move_uploaded_file($tmp, $path . '/' . $actual_image_name)){

                    $csv = new parseCSV( $path . '/' . $actual_image_name );

                    $records = sizeof( $csv->data );
                    
                    $data = array_keys( $csv->data[0] );
                    $node = '';
                    for( $c=0; $c <= sizeof($data); $c++ ){
                        // $node .= "<li>{" . trim(preg_replace('<\W+>', "_", $data[$c]), "_") . "}</li>";
                        $node .= ( $data[$c] ) ? "<li class='draggable'><code>{" . trim( $data[$c] ) . "}</code></li>" : '';
                    }

                    echo "<div class='updated autohide'>
                            <p>CSV has been successfully uploaded. There are <strong>" . sizeof( $csv->data ) . "</strong> records found. Now help me prepare your data by dragging the items from the right to any textbox in the left.</p>
                        </div>
                        
                        <div class='csv-tree-wrap'>

                            <div class='two-third' style='width: 75%; float: left;'>
                                <h3 style='color: #999;'>Drag elements from the right to the corresponding textbox below <a class='tooltip' href='#' onclick='return false;' rel='tooltip' title='The input fields below are copycat of Yoast SEO metabox which will be passed as custom fields during import. Help me identify your data by dragging the items from the right to any of the textbox below.'>&nbsp;</a></h3>
                                <div id='csv_xml_form'>
                                    <fieldset>
                                        <legend>Post or Page ID <a class='tooltip' href='#' onclick='return false;' rel='tooltip' title='Post or page ID is required.'>&nbsp;</a></legend>
                                        <div class='drag-element'><input readonly style='background: #fff;' type='text' id='csv-column-id' class='widefat droppable' value=''></div>
                                    </fieldset>
                                    <fieldset>
                                        <legend>SEO Meta Title</legend>
                                        <div class='drag-element'><input readonly style='background: #fff;' type='text' id='csv-column-title' class='widefat droppable' value=''></div>
                                    </fieldset>
                                    <fieldset>
                                        <legend>SEO Meta Description</legend>
                                        <div class='drag-element'><input readonly style='background: #fff;' type='text' id='csv-column-description' class='widefat droppable' value=''></div>
                                    </fieldset>
                                    <fieldset>
                                        <legend>Focus Keywords</legend>
                                        <div class='drag-element'><input readonly style='background: #fff;' type='text' id='csv-column-keywords' class='widefat droppable' value=''></div>
                                    </fieldset>
                                </div>
                            </div>
                            
                            <div class='third' style='width: 25%; float: right;'>
                                <h3 style='color: #999;'>Record #1 out of " . $records . " <a class='tooltip' href='#' onclick='return false;' rel='tooltip' title='These are the columns found on your CSV file. Just drag one of these items to the corresponding textbox in the left'>&nbsp;</a></h3>
                                <ul style='background: #fff; padding: 10px;'>" . $node . "</ul>
                            </div>    

                            <div style='clear: both;'></div>

                        </div><!-- end .csv-tree-wrap -->
                            
                        <p><input type='submit' id='start-import' class='button button-primary' data-file='" . $path . '/' . $actual_image_name . "' value='Start Import' data-json='" . json_encode( $data ) . "'></p>";

                }
                else{
                    echo "Fail upload folder with read access.";
                }
            }
            else
            echo "File size max 8 MB";                    
        }
        else{
            echo '<div class="error"><p>Invalid file format. <a href="?page=fl-yoast-csv&tab=csv_import">Please try again</a>.</p></div>';  
        }
    }
        
    else
        echo "Please select a file..!";
        
    exit;
}

function getExtension($str){

         $i = strrpos($str,".");
         if (!$i) { return ""; } 

         $l = strlen($str) - $i;
         $ext = substr($str,$i+1,$l);
         return $ext;
}

?>