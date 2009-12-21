<?php
include('lib/microbus.php');

// go to "/hi"
function get_hi(){
	echo "<h1>hello world</h1>";
}

// ride the micro bus;
Microbus::ride();
?>