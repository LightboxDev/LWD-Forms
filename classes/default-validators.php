<?php

class LWDValidators extends LWDValidation {

	function __construct()
	{
		$LWDValidation = LWDValidation::getInstance();
		$LWDValidation->add_rule( 'required', array( &$this, 'required' ), 1, 'is a required field.' );
		$LWDValidation->add_rule( 'email', array( &$this, 'email' ), 1, 'is not a valid email address.' );
		$LWDValidation->add_rule( 'available_email', array( &$this, 'available_email' ), 1, 'is in use by another account.' );
		$LWDValidation->add_rule( 'available_user', array( &$this, 'available_user' ), 1, 'is in use by another account.' );
		$LWDValidation->add_rule( 'valid_user', array( &$this, 'valid_user' ), 1, 'is an invalid username.' );
		$LWDValidation->add_rule( 'valid_password', array( &$this, 'valid_password' ), 2, 'is incorrect.' );
		$LWDValidation->add_rule( 'postcode', array( &$this, 'postcode' ), 1, 'is not a valid UK postcode.' );
		$LWDValidation->add_rule( 'telephone', array( &$this, 'telephone' ), 1, 'is not a valid telephone number.' );
		$LWDValidation->add_rule( 'min_length', array( &$this, 'min_length' ), 2, 'does not meet the minimum length requirement of {arg1} characters.' );
		$LWDValidation->add_rule( 'max_length', array( &$this, 'max_length' ), 2, 'exceeds the maximum length of {arg1} characters.' );
		$LWDValidation->add_rule( 'min_words', array( &$this, 'min_words' ), 2, 'does not meet the minimum word requirement of {arg1} characters.' );
		$LWDValidation->add_rule( 'max_words', array( &$this, 'max_words' ), 2, 'exceeds the maximum word count of {arg1} characters.' );
		$LWDValidation->add_rule( 'minimum', array( &$this, 'minimum' ), 2, 'does not meet the minimum requirement of {arg1} options.' );
		$LWDValidation->add_rule( 'maximum', array( &$this, 'maximum' ), 2, 'exceeds the maximum of {arg1} options.' );
	}


	public function required( $value )
	{
		// NEEDS CHANGING SO INSTEAD OF CHECKING IF VALUE IS ARRAY
		// TO IF IT EXPECTS AN ARRAY - EG CHECK ELEMENT IS CHECKBOX,
		// RADIO OR MULTI SELECT
		if( is_array($value) && count((array)$value) < 1 )
		{
			return false;
		}

		if( is_string($value) && strlen(trim($value)) == 0 )
		{
			return false;
		}
		
		return true;
	}

	public function email( $email )
	{

		if( filter_var( $email, FILTER_VALIDATE_EMAIL ) )
		{
			return true;
		}

		return false;

	}

	public function available_email( $email )
	{
		if( !$email )
			return true;

		if( email_exists( $email ) )
			return false;

		return true;
	}

	public function available_user( $user )
	{
		if( !$user )
			return true;

		if( username_exists( $user ) )
			return false;

		return true;
	}

	public function valid_user( $user )
	{
		if( username_exists( $user ) )
			return true;

		return false;
	}

	public function valid_password( $pass, $userfield )
	{
		if( !isset( $_POST[$userfield] ) )
			return false;

		$user = get_user_by( 'login', $_POST[$userfield] );

		if( !$user )
			return false;

		if( wp_check_password( $pass, $user->data->user_pass, $user->ID ) )
			return true;

		return false;
	}

	public function min_length( $value, $len )
	{
		if( is_string( $len ) ) $len = intval( $len );
	
		if(  strlen(trim($value)) < $len )
		{
			return false;
		}

		return true;
	}


	public function max_length( $value, $len )
	{
		if( is_string($len) ) $len = intval($len);
		if(  strlen(trim($value)) >= $len )
		{
			return false;
		}

		return true;
	}

	public function min_words( $value, $len )
	{
		if( is_string( $len ) ) $len = intval( $len );

		$value = count( explode( ' ', trim($value) ) );

		if( $value < $len )
		{
			return false;
		}

		return true;
	}


	public function max_words( $value, $len )
	{
		if( is_string($len) ) $len = intval($len);

		$value = count( explode( ' ', trim($value) ) );

		if( $value > $len )
		{
			return false;
		}

		return true;
	}

	public function minimum( $value, $len )
	{
		if( count($value) < $len )
		{
			return false;
		}

		return true;
	}

	public function maximum( $value, $len )
	{
		if( count($value) > $len )
		{
			return false;
		}

		return true;
	}

	public function postcode( $value )
	{
		$postcode = strtoupper(str_replace(' ','',$value));
	    if(preg_match("/^[A-Z]{1,2}[0-9]{2,3}[A-Z]{2}$/",$postcode) || preg_match("/^[A-Z]{1,2}[0-9]{1}[A-Z]{1}[0-9]{1}[A-Z]{2}$/",$postcode) || preg_match("/^GIR0[A-Z]{2}$/",$postcode))
	    {
	        return true;
	    }
	    else
	    {
	        return false;
	    }
	}

	public function telephone( $value )
	{
		$numbersOnly = ereg_replace("[^0-9]", "", $string);
		$numberOfDigits = strlen($numbersOnly);
		if ($numberOfDigits == 7 || $numberOfDigits == 10 || $numberOfDigits == 11 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	// public function file( $value )
	// {

	// $rule_stack[$rule] = new ValidationRule('BetterValidation::required', false);

	// }


	// public function matches( $value )
	// {

	//		$rule_stack[$rule] = new ValidationRuleComparison('BetterValidation::value', '==', $value);

	// }


	// public function regex( $value,  )
	// {

	// 	$rule_stack[$rule] = new ValidationRuleRegex($value);

	// }

}
new LWDValidators();