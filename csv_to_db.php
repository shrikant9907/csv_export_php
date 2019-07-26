<?php

function csv_to_array($file_path) {

	$csv_array = []; 
	if(($h = fopen("{$file_path}", "r")) !== FALSE) {
	  while (($data = fgetcsv($h, 1000, ",")) !== FALSE)   {
	    $csv_array[] = $data;		
	  }
	  fclose($h);
	}

	return $csv_array;
}

add_action('wp_head', 'csv_import_call');
function csv_import_call() {

	if(isset($_GET['import_anl']) && ($_GET['import_anl']==1)) { 

		$upload_dir = wp_upload_dir();
		$file_name 	= 'ANGELIUM_ANL_new.csv'; 
		$file_path 	= $upload_dir['path'].'/'.$file_name; 
		$csvarr 	= csv_to_array($file_path);
		
		$output['status'] 			= 404;
		$output['message'] 			= 'No record found.';
		$output['total_records'] 	= 0;
		$output['updated_accounts']	= 0;
		$output['created_accounts'] = 0;
		$output['created'] 			= '';

		if($csvarr) {
			$total_count 	= 0;
			$updated_count 	= 0;
			$created_count = 0;
			foreach($csvarr as $csv_key => $csv_value) {
				if($csv_key!=0) {

					// WordPress User check
					$username 	=  trim($csv_value['1']);
					$email 		=  trim($csv_value['2']);
					$anl_value 	=  trim($csv_value['8']); 

					$anl_value = preg_replace('/[^0-9]/', '', $anl_value); 
					
					if(($email!='') && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$user = get_user_by( 'email', trim($email));
						if($user) {

							$user_id = $user->ID; 

							// The ANL is stored in post meta
							// update_post_meta($user_id, 'anl_count', $anl_value);
							// update_user_meta($user_id, 'anl_count', $anl_value);
							
							$updated_count++;

						} else {
							
							$created[] = $email;
							
							// Create Account
							$new_password = wp_generate_password( 8, false );
							$userdata = array(
							    'user_login' 	=>  $email,
							    'display_name' 	=>  $username,
							    'user_email' 	=>  $email,
							    'first_name' 	=> 	$username, 
                       			'last_name' 	=> 	'',
                       			'role'			=> 	get_option('default_role'),
 							    'user_pass'  	=>  $new_password 
							); 

							$newuser_id = wp_insert_user( $userdata );

							if(!is_wp_error( $newuser_id ) ) { 

								// The ANL is stored in post meta
							    // update_post_meta($newuser_id, 'anl_count', $anl_value); 
							    // update_user_meta($newuser_id, 'anl_count', $anl_value); 
							} 

							$created_count++; 

						} 
						$total_count++;
					}				
				}
			}

			$output['status'] 			= 200;
			$output['message'] 			= 'User details updated.';
			$output['total_records'] 	= $total_count;
			$output['updated_accounts']	= $updated_count;
			$output['created_accounts'] = $created_count;
			$output['created'] 			= $created; 

		} else {
			$output['status'] 			= 404;
			$output['message'] 			= 'No record found in csv file.';
		}

	} 
 
	// show records
	echo '<pre>';
	print_r($output);
	echo '</pre>';
}
