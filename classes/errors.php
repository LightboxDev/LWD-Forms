<?php

class LWDErrors {

	function create( $error )
	{
		// array_push($_SESSION['formdata']['errors'], $error);
		return $error;
	}

	function clear( $all = true )
	{
		return true;
	}
}
