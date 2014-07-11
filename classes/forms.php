<?php
/**
 * THIS CLASS ALLOWS MANIPULATING FORM INPUT
 * AND VALIDATION A LOT EASIER
 *
 * IT ALSO PROVIDES AN EASY METHOD OF
 * POST/PROCESS/GET EXCEPT WITH SESSIONS
 */
class LWDForms {

	/**
	 * THE MASTER FORMS VARIABLE CONTAINING ALL 
	 * CREATED FORMS
	 */

	public $forms = array();

	public $current_form = -1;

	/**
	 * DEFAULTS FOR A FORM
	 */

	private $form_defaults = array(
		'id'				=>	NULL,
		'redirect'			=>	NULL,
		'method'			=>	'GET',
		'hook'				=>	NULL,
		'saveable'			=>	false,
		'response_position'	=> 'element',
		'elements'			=>	array(),
	);

	private $element_defaults = array(
		'type'					=>	'text',
		'label_position'		=>	'before',
		'tip_position'  		=>	'before',
		'condition_operator' 	=>  'AND'
	);

	/** 
	 * BEGIN SINGLETON - why? Have an LWDForm class extend this instead...
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
	 * DETECT FORM DATA AND BEGIN PROCESSING
	 */
	
	public function form_redirect()
	{
		// GET FORM DATA FROM POST OR GET
		$httpdata = $this->get_form_data();

		// CHECK IF FORM VARIABLE SET AND IF FORM EXISTS
		if( isset($httpdata['lwd_forms']) && isset($this->forms[$httpdata['lwd_forms']]) )
		{
			// ALL IS WELL, PROCESS THE FORM (AND STORE SESSION DATA?)
			$this->process_form( $httpdata['lwd_forms'] );

			// SESSION DATA IS GOOD, SO PERFORM REDIRECT
			// header( 'Location: ' + $this->redirect );
			header( 'Location: ' . $this->forms[$httpdata['lwd_forms']]['redirect'] );
			exit;
		}
	}

	public function add_scripts()
	{
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'lwd-forms', LWD_FORMS_URL . 'scripts/main.js' );
	}

	/**
	 * REGISTER A FORM INTO THE SYSTEM
	 */
	public function create( $args )
	{
		// MERGE DEFAULT VALUES WITH INPUTTED
		$args = array_merge($this->form_defaults, $args);

		// IF THERE IS NO ID THROW AN ERROR
		if( !isset( $args['id'] ) )
			return LWDErrors::create('No ID given for form');

		// IF IT USES A DUPLICATE ID, THROW AN ERROR
		if( isset($this->forms[ $args['id'] ]) )
			return LWDErrors::create('A form with this handle already exists');

		// IF REDIRECT IS NOT SET, THEN SET IT TO CURRENT PAGE
		if( !$args['redirect'] )
			$args['redirect'] = $_SERVER['REQUEST_URI'];

		// GRAB ID FROM ARGUMENTS AND UNSET IT
		$id = $args['id'];
		unset($args['id']);

		// STORE ARGUMENTS IN GLOBAL RELATIVE TO ID
		$this->forms[ $id ] = $args;

		// HOOK THE REDIRECT FOR THE FORM
		add_action( 'template_redirect', array( &$this, 'form_redirect' ) );

		add_action( 'wp_enqueue_scripts', array( &$this, 'add_scripts' ) );


		return true;
	}

	/**
	 * OUTPUTS THE HEAD OF THE FORM WITH ANY HIDDEN INPUTS
	 */
	public function form_head ( $id )
	{
		if( !isset($this->forms[ $id ]) )
			return LWDErrors::create('No form found matching this ID');

		$this->set_current_form( $id );

		$method = $this->forms[ $id ]['method'];
		// $action = site_url( );
		// SENDING POST DATA TO THE HOME PAGE SEEMS TO MESS THINGS UP?
		$action = '';

		$form .= '<form class="lwd-form" id="lwd_form-' . $id . '" method="' . $method . '" action="' . $action . '">';
		$form .= '<input type="hidden" name="lwd_forms" value="' . $id .'" />';
		$form .= '<input type="hidden" name="lwd_redirect" value="' . $this->forms[ $id ]['redirect'] . '" />';

		if( 'top' == $this->forms[$id]['response_position'] )
		{
			$form .= '<div class="lwd-form-response">'.$this->form_error().'</div>';
		}

		return $form;
	}

	/**
	 * OUTPUTS THE FOOT OF THE FORM AND CLEARS FORMS SESSION
	 */
	public function form_foot ( )
	{
		if( !$id = $this->get_current_form() )
			return LWDErrors::create('Not in form loop.');

		$form = '';

		if( 'top' == $this->forms[$id]['response_position'] )
		{
			$form .= '<div class="lwd-form-response">'.$this->form_error().'</div>';
		}

		$form .= '</form>';

		// NEED TO CLEAR SESSION HERE
		$_SESSION['lwd_forms'][$id] = NULL;

		$this->set_current_form();


		return $form;
	}

	// RETURNS FALSE IF NO ERRORS
	public function form_error( $element = null )
	{
		if( !$id = $this->get_current_form() )
			return LWDErrors::create('Not in form loop.');

		if( empty($_SESSION['lwd_forms'][$id]['errors']) )
			return false;

		$response = false;
		if( $element )
		{
			// OUTPUT RESPONSE FOR A SINGLE ELEMENT
			$validations = $_SESSION['lwd_forms'][$id]['errors'][$element];

			// CORRECT ME IF IM WRONG, THIS ISNT ACTUALLY NEEDED?
			// if( !$validations )
			// 	return LWDErrors::create('Element does not exist in form.');

			if( !$validations )
				return false;

			foreach( $validations as $error => $type )
			{
				// IF RESPONSE IS FALSE (FAIL)
				if( !$type )
				{
					// PASS VALIDATION RULE AND ELEMENT TO FUNCTION
					$response .= '<p><strong>'.$this->forms[$id]['elements'][$element]['label'].'</strong> ';
					$response .= $this->get_error_message( $error, $element ) . '</p>';
				}
			}
		}
		else
		{
			//OUTPUT RESPONSE FOR WHOLE FORM
			foreach( $_SESSION['lwd_forms'][$id]['errors'] as $element => $validations )
			{
				foreach( $validations as $error => $type )
				{
					// IF RESPONSE IS FALSE (FAIL)
					if( !$type )
					{
						$response .= '<p><strong>'.$this->forms[$id]['elements'][$element]['label'].'</strong> ';
						$response .= $this->get_error_message( $error, $element ) . '</p>';
					}
				}

			}
		}

		return $response;
	}

	public function form_element( $name )
	{
		if( !$id = $this->get_current_form() )
			return LWDErrors::create('Not in form loop.');

		if( !$properties = $this->forms[$id]['elements'][$name] )
			return LWDErrors::create('No element named ' . $name . ' has been declared.');

		// MERGE DEFAULT VALUES WITH INPUTTED
		$properties = array_merge($this->element_defaults, $properties);

		$httpdata = $_SESSION['lwd_forms'][$id]['data'];

		// SETUP LABEL
		if( $properties['label'] )
		{
			$main_label = '<label class="lwd-label" for="'.$name.'">';
			$main_label .= $properties['label'];
			$main_label .= '</label>';
		}

		// SETUP TIP
		if( $properties['tip'] )
		{
			$tip = '<span class="lwd-tip" for="'.$name.'">';
			$tip .= $properties['tip'];
			$tip .= '</span>';
		}

		// SETUP ERRORS/RESPONSE
		// ONLY SHOW ERRORS ON SUBMISSION, NOT SAVE
		if( $this->form_error($name) && !isset($httpdata['lwd_save']) )
		{
			$error = '<div class="lwd-element-response">'.$this->form_error($name).'</div>';
		}

		// PREFETCH ELEMENT VALUE
		$prefetch = $this->get_element_value( $name );

		$response = '<div class="lwd-element-wrap';

		//CLASSES HERE
		if( $this->form_error( $name ) )
		{
			$response .= ' lwd-element-has-error';
		}


		$response .= '" id="lwd-element-'.$name.'"';

		if( $properties['conditions'] )
		{
			$response .= ' data-condition="' . $properties['condition_operator'] . '"';
			$condcount = 0;
			foreach( $properties['conditions'] as $condition )
			{
				$condcount ++;

				$response .= ' data-condition-'.$condcount.'-'.$condition['element'].'="'.$condition['value'].'"';
			}
		}

		$response .= '>' . $value;


		$response .= '<div class="lwd-'.$properties['type'].'-wrap lwd-label-'.$properties['label_position'].'">';

		if( 'before' == $properties['label_position'] )
		{
			$response .= $main_label;
		}

		if( 'before' == $properties['tip_position'] )
		{
			$response .= $tip;
		}

		switch( $properties['type'] )
		{
			case 'textarea':
			{
				$response .= '<span class="lwd-textarea">';
				$response .= '<textarea name="'.$name.'" id="lwd-'.$id.'-'.$name.'"';

				if( 'placeholder' == $properties['label_position'] )
				{
					$response .= ' placeholder="'.$properties['label'].'"';
				}
				else if( 'placeholder' == $properties['tip_position'] )
				{
					$response .= ' placeholder="'.$properties['tip'].'"';
				}

				$response .= ' >';

				if( is_string($prefetch) && $prefetch )
				{
					$response .= $prefetch;
				}

				$response .= '</textarea>';
				$response .= '</span>';
				break;
			}
			case 'checkbox': case 'radio':
			{
				if( empty($properties['options']) )
					return LWDErrors::create('No options provided for ' . $properties['type'] . '');

				foreach( $properties['options'] as $item => $label )
				{
					$response .= '<div class="lwd-' . $properties['type'] . '-item">';

						$response .= '<span class="lwd-' . $properties['type'] . '">';

							$response .= '<input type="' . $properties['type'] . '" name="'.$name.'[]" value="'.$item.'" id="lwd-'.$id.'-'.$name.'-'.$item.'"';

							if( in_array( $item, (array)$prefetch) )
							{
								$response .= ' checked="checked"';
							}

							$response .= ' />';

						$response .= '</span>';
						$response .= '<label class="lwd-' . $properties['type'] . '-label" for="lwd-'.$id.'-'.$name.'-'.$item.'">';

							$response .= $label;

						$response .= '</label>';

					$response .= '</div>';
				}
				break;
			}
			default: //TEXT, EMAIL, PASSWORD
			{
				$response .= '<span class="lwd-' . $properties['type'] . '">';
				$response .= '<input type="' . $properties['type'] . '" name="'.$name.'"';

				if( 'placeholder' == $properties['label_position'] )
				{
					$response .= ' placeholder="'.$properties['label'].'"';
				}
				else if( 'placeholder' == $properties['tip_position'] )
				{
					$response .= ' placeholder="'.$properties['tip'].'"';
				}

				if( is_string($prefetch) && $prefetch )
				{
					$response .= ' value="' . $prefetch . '"';
				}

				$response .= ' />';
				$response .= '</span>';
				break;
			}
		}

		if( 'after' == $properties['label_position'] )
		{
			$response .= $main_label;
		}

		if( 'after' == $properties['tip_position'] )
		{
			$response .= $tip;
		}

		$response .= '</div>';


		if( 'element' == $this->forms[$id]['response_position'] )
		{
			$response .= $error;
		}

		$response .= '</div>';

		return $response;
	}

	/**
	 * Set current form context - if null then reset it
	 * @param string $id identifier for form
	 */
	public function set_current_form( $id = NULL )
	{
		if( $id != NULL )
		{
			$this->current_form = $id;
			return true;
		}
		else
		{
			$this->current_form = -1;
			return false;
		}
	}

	/**
	 * PROCESS THE FORMS INPUTS AND VALIDATE
	 */

	public function process_form( $id )
	{
		$httpdata = $this->get_form_data( $httpdata );

		// THIS HAS BEEN REFACTORED, VALIDATION WILL NOW ALWAYS RUN (EVEN ON SAVE)
		// THIS IS SO THAT ERROR CLASSES STILL GET ADDED TO ELEMENTS BUT NOW WHEN
		// A FORM IS SAVED IT SIMPLY DOES NOT DISPLAY THE ERROR, USEFUL FOR ABOUT
		// A MILLION REASONS
		$LWDValidation = LWDValidation::getInstance();
		$errors = $LWDValidation->process( $httpdata, $this->forms[$id]['elements'] );


		// IF USER IS LOGGED IN THEN SAVE POSTDATA
		if( is_user_logged_in() )
		{
			$this->save_user_data( get_current_user_id(), $id, $httpdata );
		}

		// ALSO STORE THE SENT DATA IN THE SESSION FOR RE-USE ON ELEMENTS
		$this->add_session_data( $id, $httpdata );

		// IF THE HOOK IS SET, CALLABLE AND IT IS SUBMITTED NOT SAVED AND NO ERRORS
		if( $this->forms[$id]['hook'] &&
			is_callable( $this->forms[$id]['hook'] ) &&
			!isset($httpdata['lwd_save']) &&
			!count($errors)
		)
		{
			// WE NOW ALSO NEED TO CLEAN THE SAVED FORM DATA
			$this->clear_user_data( get_current_user_id(), $id );

			$this->clear_session_data( $id );

			// NEED TO ADD A FUNCTION HERE TO FILTER OUT USELESS SENT DATA,
			// ENSURING ONLY ACTUAL REGISTERED ELEMENTS GET SENT TO HOOK
			call_user_func_array( $this->forms[$id]['hook'], array($httpdata) );

			return true;
		}


	}

	public function add_session_data( $id, $data )
	{
		$_SESSION['lwd_forms'][$id]['data'] = $data;
	}

	public function get_session_data( $id )
	{
		return stripslashes_deep( $_SESSION['lwd_forms'][$id]['data'] );
	}

	public function clear_session_data( $id )
	{
		$_SESSION['lwd_forms'][$id] = NULL;
		return true;
	}

	public function get_error_message( $error, $element )
	{
		$LWDValidation = LWDValidation::getInstance();


		if( $LWDValidation->has_validation_args( $error ) )
		{
			// NEED TO FETCH AND INJECT ANY ARGS INTO STRING
			$rule = $LWDValidation->strip_validation_args( $error );
			$args = $LWDValidation->get_validation_args( $error );

			$response = $LWDValidation->error_messages[$rule];

			if( $args )
			{
				$count = 1;
				foreach( $args as $arg )
				{
					$response = str_replace( '{arg'.$count.'}', (string)$arg, $response );
					$count ++;
				}
			}
		}
		else
		{
			// STRAIGHT FORWARD
			$response = $LWDValidation->error_messages[$error];
		}

		return $response;
		// WE NOW NEED TO ADD ANY ARGS TO THIS
	}

	public function get_element_value( $element )
	{
		if( !$id = $this->get_current_form() )
			return LWDErrors::create('Not in form loop.');

		// PRIORITISE SESSION DATA OVER SAVED DATA
		$data = $this->get_session_data( $id );

		// IF DATA IS SENT FOR ELEMENT
		if( $data[$element] )
		{
			return $data[$element];
		}

		// IF DATA HAS BEEN PREVIOUSLY SAVED FOR ELEMENT
		if( $this->has_user_data( get_current_user_id(), $id ) )
		{
			$data = $this->get_user_data( get_current_user_id(), $id );

			if( $data[$element] )
			{
				return $data[$element];
			}
		}

		// IF THE ELEMENT HAS A DEFAULT VALUE
		if( $this->forms[$id]['elements'][$element]['default'] )
		{
			return $this->forms[$id]['elements'][$element]['default'];
		}

		return false;
	}

	public function save_user_data( $user, $form, $data )
	{
		if( !$this->forms[$form]['saveable'] )
			return null;

		// UPDATE DATA FOR USER
		if( update_user_meta( $user, 'lwd_saved_form_'.$form, $this->encode_data( $data ) ) )
			return true;
		else
			return LWDErrors::create('Unable to save user data for User:' . $user_id . ' with form: ' . $form );
	}

	public function clear_user_data( $user, $form )
	{
		// UPDATE DATA FOR USER
		if( !$this->forms[$form]['saveable'] )
			return null;

		if( delete_user_meta( $user, 'lwd_saved_form_'.$form ) )
			return true;
		else
			return LWDErrors::create('Unable to clear user data for User:' . $user_id . ' with form: ' . $form );
	}

	public function get_user_data( $user, $form )
	{
		if( !$this->forms[$form]['saveable'] )
			return null;

		return $this->decode_data( get_user_meta( $user, 'lwd_saved_form_' . $form, true ) ); 
	}

	public function has_user_data( $user, $form )
	{
		if( !get_user_meta( $user, 'lwd_saved_form_' . $form, true ) )
			return false;
		else
			return true;
	}

	public function encode_data( $input )
	{
		array_walk_recursive( $input, function (&$value) {
		    $value = htmlentities($value, ENT_QUOTES);
		});

		$data = $input;

		$data = serialize( $data );

		$data = base64_encode( $data );

		return $data;
	}

	public function decode_data( $input )
	{
		if( is_string( $input ) )
		{
			if( false !== base64_decode($input, true) )
			{
				$input = base64_decode( $input );
				$input = unserialize( $input );

			}
		}
		
		$input = stripslashes_deep( $input );

		return $input;
	}

	public function stripslashes_deep( $value )
	{
		$value = is_array($value) ?
			array_map(array(&$this,'stripslashes_deep'), $value) :
			stripslashes($value);

		return $value;
	}

	// RETURN THE CURRENT FORM (ANY CODE BETWEEN FORM_HEAD AND FORM_FOOT)
	public function get_current_form()
	{
		if( !$this->current_form )
		{
			return false;
		}
		else
		{
			return $this->current_form;
		}
	}

	// RETURN THE CURRENT FORM (WHICH HAS BEEN POSTED)
	public function get_posted_form()
	{
		$data = $this->get_form_data();
		return $data['lwd_forms'];
	}

	/**
	 * GET THE FORM DATA FROM EITHER POST OR GET
	 * POST TAKES PRIORITY
	 */

	public function get_form_data()
	{
		$httpdata = $_POST ? $_POST : $_GET;
		return $httpdata;
	}

}