<?php 

$db['host'] = 'localhost';
$db['username'] = 'root';
$db['password'] = '';
$db['database'] = 'login_db2';
foreach($db as $key => $value){
	define(strtoupper($key), $value);
}

$con = mysqli_connect(HOST, USERNAME, PASSWORD, DATABASE);

/**
 * Database Helper Functions
 */

function row_count($result){
	return mysqli_num_rows($result);
}

function escape($string) {
	global $con;
	return mysqli_real_escape_string($con, $string);
}

function confirm($result) {	global $con;
	if(!$result) {
		die("QUERY FAILED" . mysqli_error($con));
	}
}

function query($query) {
	global $con;
	$result =  mysqli_query($con, $query);
	confirm($result);
	return $result;
}

function fetch_array($result) {
	global $con;
	return mysqli_fetch_array($result);
}


 ?>