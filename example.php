<?php
/*
	This is an example of available options
	copy this to application.php to test.
*/

include('lib/microbus.php');

// this is very sinatra, but is only one of the options available
if (get('/')){
	echo "Hello World";
}

// you can pass a callback as the second parameter.
get('/callback','callback');
function callback(){
	echo "Hey! I'm a callback";
}

// this is better, this will be called automagically when there's a get request to '/automagic' 
function get_automagic(){
	echo "I'm called automagically just by being there";
	echo "<form method='post'>";
	echo "<input type='text' name='name'>";
	echo "<input type='submit'>";
	echo "</form>";
}

// this is better, this will be called automagically when there's a post request to '/automagic' 
function post_automagic($params = null){
	echo "I'm called automagically and here's the post request";
	pr($params['data']);
}

// You'd prefer some advance OOP love? Sure!
class Application{
	
	// try going to '/love' to try this;
	function get_love(){
		echo "All you ned is love";
	}
	
	// writing all code in this file is dumb? try grabbing a view
	function get_view(){
		View::render('view');
	}
	
}

// One more thing.

// If nothing else matches, we'll just try to load the view 
// named the same as the path. Example: '/default' will load 'default.php' in the views folder.
// How's that for flexiblity?

Microbus::ride();

?>