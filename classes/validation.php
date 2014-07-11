<?php

class LWDValidation extends LWDForms {

	public $rules = array();

	/** 
	 * BEGIN SINGLETON FRAMEWORK
	 */

	public static function getInstance()
	{
		static $instance = null;
		if (null === $instance) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	* Protected constructor to prevent creating a new instance of the
	* *Singleton* via the `new` operator from outside of this class.
	*/
	protected function __construct(){}

	/**
	* Private clone method to prevent cloning of the instance of the
	* *Singleton* instance.
	*
	* @return void
	*/
	private function __clone(){}

	/**
	* Private unserialize method to prevent unserializing of the *Singleton*
	* instance.
	*
	* @return void
	*/
	private function __wakeup(){}

	/**
	 * END SINGLETON FRAMEWORK
	 */
	

	/**
	 * PROCESS VALIDATION FOR A FIELDSET AND FORM
	 * @param  array $data THIS IS THE POST/GET DATA TO VALIDATE
	 * @param  array $validations THIS IS THE SET OF RULES TO VALIDATE AGAINST
	 * @return null
	 */
	public function process( $data, $validations )
	{
		if( !is_array( $data ) )
			return LWDErrors::create('Invalid argument supplied for validation data');

		if( !is_array( $validations ) )
			return LWDErrors::create('Invalid argument supplied for validation arguments');

		/**
		 * EXPECTED DATA FORMAT IS POST/GET DATA
		 *
		 * EXPECTED ARGUMENTS IS AN ARRAY WHICH INDEXES
		 * MATCH THE DATA AND CONTAIN AN ARRAY OF TESTS
		 */
		
		// var_dump($data);
		
		// LOOP OVER VALIDATION RULES, ELEMENT IS THE NAME OF THE VARIABLE
		// AND VALIDATION IS A STRING OR ARRAY CONTAING RULES
		foreach( $validations as $element => $properties )
		{
			// LOOP OVER THE RULE
			$validation = $properties['validation'];
			
			// FIRST WE NEED TO CHECK CONDITIONALS TO ENSURE THAT WE ACTUALLY NEED
			// TO VALIDATE THIS DATA
			$continue = $next = 0;
			$condition_operator = $properties['condition_operator'];
			if( !$condition_operator ) $condition_operator = 'AND';


			if( $properties['conditions'] && !empty($properties['conditions']) )
			{
				foreach( $properties['conditions'] as $condition )
				{
					if( $data[$condition['element']] != $condition['value'] &&
						!in_array($condition['value'], (array)$data[$condition['element']]) )
					{
						$continue ++;

						// IF THIS NEEDS TO MEET ALL CONDITIONS THEN QUIT NOW AS IT FAILED
						if( 'AND' == $condition_operator )
							$next = true;
					}
				}

				if( $continue >= count($properties['conditions']) && 'OR' == $condition_operator )
				{
					$next = true;
				}

				if( $next == true )
					continue;

			}

			// var_dump( $properties );

			$data = $this->get_form_data();
			$value = $data[$element];
			if( is_array($validation) )
			{
				// IF AN ARRAY IS SUPPLIED LOOP OVER IT 
				// AND VALIDATE
				$validate_array = $validation;
				foreach($validate_array as $validate)
				{
					// CALL THE VALIDATION FOR RULE AND ELEMENT
					$response = $this->validate(  $value, $validate );
					$this->add_response( $element, $validate, $response );
				}
			}
			elseif ( strpos($validation, '|') )
			{
				// IF IT IS A STRING SEPERATED BY PIPES THEN
				// EXPLODE IT AND LOOP
				$validate_array = explode('|', $validation);
				foreach($validate_array as $validate)
				{
					// CALL THE VALIDATION FOR RULE AND ELEMENT
					$response = $this->validate(  $value, $validate );
					$this->add_response( $element, $validate, $response );
				}
			}
			elseif ( is_string($validation) )
			{
				// THIS IS A SINGLE STRING BASED VALIDATION
				// CALL THE VALIDATION FOR RULE AND ELEMENT
				$response = $this->validate(  $value, $validation );
				$this->add_response( $element, $validation, $response );
			}
		}

		return $this->errors;
	}

	public function validate( $element, $validation)
	{
		// ENSURE ARGS IS EMPTY - MAY NEED DEPRECATING
		$args = array();

		// IF THERE ARE ARGUMENTS (BRACKETS) IN THIS VALIDATION,
		// STRIP AND STORE THEM SO THAT THE RULE CAN BE PROPERLY MATCHED
		$args = $this->get_validation_args( $validation );
		$validation = $this->strip_validation_args( $validation );

		// ENSURE RULE IS REGISTERED IN THE SYSTEM
		if( $this->rules[$validation] )
		{
			// PROCESS THE VALIDATION
			// LOOK AT DEPRECATING THIS INTO A SINGLE BLOCK
			if( $this->rules[$validation]['arguments'] > 1 )
			{
				// PROCESSES THE VALIDATION WHERE THERE ARE MULTIPLE ARGUMENTS
				array_unshift($args, $element);
				$response = call_user_func_array( $this->rules[$validation]['function'], $args );
			}
			else
			{
				// DEFAULT NO ARGUMENTS
				$response = call_user_func( $this->rules[$validation]['function'], $element );
			}

			return $response;
		}
		else
		{
			// RULE WAS NOT FOUND
			return LWDErrors::create('Undeclared validation rule '.$validation);
		}
	}

	public function add_response( $element, $validation, $response)
	{
		$_SESSION['lwd_forms'][$this->get_posted_form()]['errors'][$element][$validation] = $response;

		if( !$response )
		{
			$this->errors[][$element][] = $validation;
		}

		return true;
	}

	public function get_validation_args( $validation )
	{
		// IF THERE ARE ARGUMENTS (BRACKETS) IN THIS VALIDATION,
		// STRIP AND STORE THEM SO THAT THE RULE CAN BE PROPERLY MATCHED
		if( !$this->has_validation_args( $validation) )
			return false;

		// STORE DATA INSIDE BRACKETS IN A VARIABLE
		preg_match("/(?<=\()(.+)(?=\))/is", $validation, $args);
		// EXPLODE THE STRING INTO AN ARRAY OF ARGUMENTS
		return explode(',', $args[0]);
	}

	public function strip_validation_args( $validation )
	{
		// IF THERE ARE ARGUMENTS (BRACKETS) IN THIS VALIDATION,
		// STRIP AND STORE THEM SO THAT THE RULE CAN BE PROPERLY MATCHED
		if( !$this->has_validation_args( $validation) )
			return $validation;

		// REMOVE THE BRACKETS AND CONTENT SO RULE CAN BE MATCHED
		return trim(preg_replace('/\s*\([^)]*\)/', '', $validation));
	}

	public function has_validation_args( $validation )
	{
		if( strpos($validation, '(') && strpos($validation, ')') )
			return true;
		else
			return false;
	}

	/**
	 * ADD A VALIDATION RULE TO THE SYSTEM , WORKS VERY SIMILAR TO ADD_ACTION
	 */
	public function add_rule( $name, $function, $args = 1, $error )
	{
		// CHECK RULE EXISTS BEFORE ADDING IT
		if( is_callable( $function ) )
		{
			// ADD RULE TO THE ARRAY OF RULES
			$this->rules[$name] = array( 'function' => $function, 'arguments' => $args );
			$this->error_messages[$name] = $error;
		}
		else
		{
			return LWDErrors::create('Unable to find rule "' . $name .'"');
		}
	}
}