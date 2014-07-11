<?php
/**
 * Plugin Name: LWD Forms
 * License: GPL2
 */


// UNIFY ERROR CREATION INTO SEPERATE FUNCTIONS, CODE IS MESSY
// ADD TYPE SUPPORTS TO VALIDATION RULES AND ALSO TYPE OUTPUT ON ELEMENT
// ADD SUBMISSION HOOK CONTAINING FORM ID AND SUCCESS/FAIL WITH GET_RAW_ERRORS FUNCTION
// ADD MINIMUM AND MAXIMUM OPTION FOR CHECKBOX AND MULTI-SELECT

// NEW ELEMENTS FORMAT BELOW

/*
'elements'		=>	array(
	'name'	=>  array(
		'type'			=> 'text',
		'label'			=> 'Full Name',
		'label_position'=> 'before', // BEFORE IS DEFAULT WITH TEXT, AFTER FOR CHECKBOX AND RADIO - ALSO ADD PLACEHOLDER OPTION
		'validation' 	=> array(
			'required',
			'min_length(20)'
		)
	),
	'categories'	=>  array(
		'type'			=> 'checkbox', // THESE OPTIONS WOULD ALSO APPLY TO RADIO AND DROPDOWN
		'label'			=> 'Full Name',
		'label_position'=> 'before', // BEFORE IS DEFAULT WITH TEXT, AFTER FOR CHECKBOX AND RADIO - ALSO ADD PLACEHOLDER OPTION
		'validation' 	=> array(
			'required', // IN A MULTI-SELECT FIELD THIS SHOULD JUST INDICATE "MINIMUM(1)"
			'maximum(2)' // CAN ONLY SELECT 2 OPTIONS
		),
		'options'		=> array(
			'education'	=>	'Education, Training and Research Award',
			'health'	=>	'Health Improvement and Promotion Award',
			'mental'	=>	'Mental Health Award',
			'healthcare'=>	'Healthcare Reservist of the Year'
		)
	),
),
*/

//DEFINE URL TO FORMS
// define(LWD_FORMS_DIR, LWD_FRAMEWORK_DIR . 'plugins/lwd-forms/' );

define(LWD_FORMS_DIR, plugin_dir_path( __FILE__ ) );
define(LWD_FORMS_URL, plugin_dir_url( __FILE__ ) );

require( LWD_FORMS_DIR . 'classes/forms.php');
require( LWD_FORMS_DIR . 'classes/validation.php');
require( LWD_FORMS_DIR . 'classes/errors.php');
require( LWD_FORMS_DIR . 'classes/default-validators.php');

/**
 * ADD A CUSTOM RULE TO THE VALIDATION SYSTEM
 * @param  string  NAME GIVEN TO VALIDATION
 * @param  function  FUNCTION FOR RULE TO CALL
 * @param  integer AMOUNT OF ARGUMENTS
 * @return bool
 */
function register_validation_rule( $name, $function, $arguments = 1 )
{

	$LWDValidation = LWDValidation::getInstance();

	return $LWDValidation->add_rule( $name, $function, $arguments );
}


/**
 * REGISTER A FORM
 * @param  array ARRAY OF ELEMENTS AND RULES
 * @return bool 
 */
function register_lwd_form( $args )
{	
	$LWDForms = LWDForms::getInstance();
	return $LWDForms->create( $args );
}

/** DISPLAY THE FORMS HEAD, SHOULD BE WRAPPED IN AN IF STATEMENT */
function lwd_form( $id )
{
	$LWDForms = LWDForms::getInstance();
	if( $form = $LWDForms->form_head( $id ) )
	{
		echo $form;
		return true;
	}

	return false;
}

function lwd_form_element( $name )
{
	$LWDForms = LWDForms::getInstance();
	echo $LWDForms->form_element( $name );
}

function lwd_conditions( $conditions )
{
	// echo ' data-condition-'.$condition_el.'="'.$condition.'"';
	if( $conditions )
	{
		$response .= ' data-condition="' . $conditions['condition_operator'] . '"';
		$condcount = 0;
		foreach( $conditions['conditions'] as $condition )
		{
			$condcount ++;

			$response .= ' data-condition-'.$condcount.'-'.$condition['element'].'="'.$condition['value'].'"';
		}
	}
	echo $response;
}

function lwd_form_submit( $value = 'Submit' )
{
	// REFACTOR INTO OOP
	echo '<input type="submit" name="lwd_submit" class="lwd_submit" value="'.$value.'" />';
}

function lwd_form_save( $value= 'Save' )
{
	// REFACTOR INTO OOP AND ALLOW DIFFERENT SAVE METHODS EG USER, SESSION ETC
	echo '<input type="submit" name="lwd_save" class="lwd_save" value="'.$value.'" />';
}

/**
 * THIS NEEDS A LOT OF WORK
 */
function lwd_form_end( )
{
	$LWDForms = LWDForms::getInstance();
	echo $LWDForms->form_foot( );
}

function lwd_form_errors( $element = null )
{
	$LWDForms = LWDForms::getInstance();
	echo $LWDForms->form_error( $element );	
}