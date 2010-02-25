<?php
/*
Plugin Name: Email PHP Errors Plugin
Plugin URI: http://www.BlogsEye.com/
Description: Catches php errors, emails the results 
Version: 1.0
Author: Keith P. Graham
Author URI: http://www.BlogsEye.com/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function kpg_set_php_errors() {
	// sets the global error handler
	set_error_handler("EmailPHPErrorHandler");
}
function EmailPHPErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
	// this uses the php standard email functions rather than the wp ones. 
	// need to get the info out of the repository. It might no be there
	$email=''; 
	$eoption='0';
	$updateData=array();
	$erclist=array();
	if (!function_exists(get_option)) return false;
	$updateData=get_option('kpg_ephp_options');
	if ($updateData==null) return false;
	if (!array_key_exists('email',$updateData)) return false;
	if (!array_key_exists('eoption',$updateData)) return false;
	$eoption=$updateData['eoption'];
	if ($eoption!=1&&$eoption!=2&&$eoption!=3) return false;
	$email=$updateData['email'];
	if (array_key_exists('erclist',$updateData)) $erclist=$updateData['erclist'];
	// check to see if we are filtering this message
	// 1 = check errors only
	// 2 = check errors and warnings
	// 3 = check all errors and notices
    switch ($eoption) {
		case 1: // this is errors only
			if ($errno!=E_USER_ERROR) return false;
			break;
		case 2: // this is errors or warnings
			if ($errno!=E_USER_ERROR && $errno!=E_USER_WARNING) return false;
			break;
		case 3: // this is all errors, warnings nad other stuff
			// everything is reported
			break;
		default:
			// unknown error
			return false;
			break;
    }
	// if we get this far we have a valid error and an email	
	$serrno="";
	switch ($errno) {
		case E_ERROR: 
			$serrno="Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted. ";
			break;
		case E_WARNING: 
			$serrno="Run-time warnings (non-fatal errors). Execution of the script is not halted. ";
			break;
		case E_NOTICE: 
			$serrno="Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script. ";
			break;
		default;
			$serrno="Unknown Error type $errno";
	}
 
	$msg="
	Error message from Wordpress.
	Error type: $serrno
	Error Msg: $errmsg
	File name: $filename
	Line Number: $linenum
	
	Message sent from Email PHP Errors Plugin.
	";
	$headers="From: $email\r\nReply-To: $email\r\n";
	$subject="Wordpress Error Message ";
	$headers1=stripslashes($headers);
	if (strlen($email)>5) {
		mail($email,$subject,$msg,$headers);
	}
	// add the data to the arraay
	$ercs=array();
	$ercs[0]=date('m/d/Y H:i:s');
	$ercs[1]=$_SERVER['REQUEST_URI'];
	$ercs[2]=html_entity_decode($_SERVER['HTTP_REFERER']);
	$ercs[3]=$_SERVER['HTTP_USER_AGENT'];
	$ercs[4]=$_SERVER['REMOTE_ADDR'];
	$ercs[5]=$errno;
	$ercs[6]=$errmsg;
	$ercs[7]=$filename;
	$ercs[8]=$linenum;
	
	//add to erclist
	array_unshift($erclist,$ercs);
	for ($j=0;$j<10;$j++) {
		if (count($erclist)>25) {
			array_pop($erclist);
		}
	}
	$updateData['erclist']=$erclist;
	update_option('kpg_ephp_options', $updateData);

	return false;

}

	add_action( 'admin_menu', 'kpg_set_php_errors_admin' );
	
function kpg_set_php_errors_admin() {
   add_options_page('Email PHP Errors', 'Email PHP Errors', 'manage_options', 'email_php_errors','kpg_set_php_errors_options');
}
function kpg_set_php_errors_options() {
// this is the quickie set for the parameters.
// two params, email and options. email is a text box. options is a number, 0,1,2,3
	$email=''; 
	$eoption='0';
	$erclist=array();
	$updateData=get_option('kpg_ephp_options');
	if ($updateData==null) $updateData=array();
	if (array_key_exists('email',$updateData)) $email=$updateData['email'];
	if (array_key_exists('eoption',$updateData)) $eoption=$updateData['eoption'];
	if (array_key_exists('erclist',$updateData)) $erclist=$updateData['erclist'];
	// now check to see if this is coming through on a post
	if ($eoption!=0&&$eoption!=1&&$eoption!=2&&$eoption!=3) $eoption=0;
    if( array_key_exists('kpg_from_ephp_form',$_POST) && $_POST[ 'kpg_from_ephp_form' ] == 'Y' ) {
        $email = $_POST[ 'kpg_ephp_email' ];
        $eoption = $_POST[ 'kpg_ephp_options' ];
		if ($eoption!=0&&$eoption!=1&&$eoption!=2&&$eoption!=3) $eoption=0;
		$updateData['email']=$email;
 		$updateData['eoption']=$eoption;
        update_option( 'kpg_ephp_options', $updateData );
?>
<div class="updated"><p><strong>Options saved.</strong></p></div>
<?php
    }
	// fill in form stuff
?>
    <div class="wrap">
   <h2>Email PHP Errors Options</h2>
<form name="form1" method="post" action="">
<input type="hidden" name="kpg_from_ephp_form" value="Y">

<table  class="form-table">
<tr><td>
Email:
<input type="text" name="kpg_ephp_email" value="<?php echo $email; ?>" size="20">
</td><td>If you want to be emailed with the errors enter your email address.</td></tr>
<tr><td>
Errors to report:
<select name="kpg_ephp_options">
	<option value="0" <?php if ($eoption==0) { ?> selected="selected" <?php } ?>>None</option>
	<option value="1"<?php if ($eoption==1) { ?> selected="selected" <?php } ?>>Errors Only</option>
	<option value="2"<?php if ($eoption==2) { ?> selected="selected" <?php } ?>>Errors and Warnings</option>
	<option value="3"<?php if ($eoption==3) { ?> selected="selected" <?php } ?>>All Errors, Warnings, Notices, etc.</option>
</select>
</td><td>Select the errors that you want to receive</td></tr>
</table><hr />

<p class="submit">
<input type="submit" name="Submit" value="Update" />
</p>

</form>
<hr/>
<?php 
	if (count($erclist)>0) {
?>
<table align="center" cellspacing="2" style="background-color:#CCCCCC;">
<tr>
<td style="background-color:#FFFFFF">Date/Time</td>
<td style="background-color:#FFFFFF">Requested Page/Referrer</td>
<td style="background-color:#FFFFFF">Browser User Agent/IP</td>
<td style="background-color:#FFFFFF">Error Number/Error Message</td>
<td style="background-color:#FFFFFF">Line Number/File Name</td>
</tr>
<?php 
	for ($j=0;$j<count($erclist);$j++) {
	$ercs=$erclist[$j];
?>

<tr>
<td style="background-color:#FFFFFF"><?PHP echo $ercs[0]; ?></td>
<td style="background-color:#FFFFFF"><?PHP echo $ercs[1]; ?><br/><?PHP echo $ercs[2]; ?></td>
<td style="background-color:#FFFFFF"><?PHP echo $ercs[3]; ?><br/><?PHP echo $ercs[4]; ?></td>
<td style="background-color:#FFFFFF"><?PHP echo $ercs[5]; ?><br/><?PHP echo $ercs[6]; ?></td>
<td style="background-color:#FFFFFF"><?PHP echo $ercs[8]; ?><br/><?PHP echo $ercs[7]; ?></td>
</tr>

<?php 
	}	// end for loop
?>
</table>
<?php 	
	
	} else {
?>
No errors recorded at this time
<?php 	
		}
?>
</div>
<?php

} // end of function
	
	
// uninstall stuff
if ( function_exists('register_uninstall_hook') ) {
	register_uninstall_hook(__FILE__, 'kpg_set_php_errors_uninstall');
}
function kpg_set_php_errors_uninstall() {
	if(!current_user_can('manage_options')) {
		die('Access Denied');
	}
	delete_option('kpg_ephp_options'); 
	return;
}


	//add_action( 'plugins_loaded', 'kpg_set_php_errors' );
	// don't need no stinking action - load it now!
	kpg_set_php_errors();

	
	
	
	 
?>