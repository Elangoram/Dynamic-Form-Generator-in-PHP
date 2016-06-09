<?php
require 'form_field_builder.php';
echo '<h1>Application Form</h1>';

//creating a object for form builder
$form = new Sample_Form_Creator();

$form_options = array(  
	array(
        'id' => 'first_name', // if 'name' param is not given. Then by default 'id' will be considered as 'name'.
        'placeholder' => 'First Name',
        'label' => 'First Name',	
    ),
    array(
        'id' => 'last_name', // if 'label' param is not given.Then by default 'id' will be splited and considered as 'label'
        'placeholder' => 'Last Name',
		'class'	=>	'last_name'
    ),
	array(
        'id' => 'email',
        'type' => 'email',
        'placeholder' => 'Email Address',
        'label' => 'Email Address'
    ),
	array(
        'id' => 'confirm_email',
        'type' => 'email',
        'placeholder' => 'Confirm Email Address',
        'label' => 'Confirm Email Address'
    ),
	array(
        'id' => 'phone_number',
        'label' => 'Phone Number',
        'placeholder' => 'Phone Number',
        'type' => 'tel'
    ),
	
		
	array(
        'id' => 'experience_type',
        'type' => 'radio',
		'label' => 'Experience Type',
        'options' => array(
			array(
				'id' => 'radio_button_yes',
				'value' => 'fresher',
				'label' => 'Fresher'
			),
			array(
				'id' => 'radio_button_no',
				'value' => 'experienced',
				'label' => 'Experienced'
			)
        )
    ),
	array(
     'id' => 'technology',
     'label' => 'Working Platform',
     'type' => 'checkbox',
     'options' => array(
			array(
				'id' => 'choice_1',
				'value' => 1,
				'label' => 'PHP'
			),
			array(
				'id' => 'choice_2',
				'value' => 2,
				'label' => 'Java'
			),
			array(
				'id' => 'choice_3',
				'value' => 3,
				'label' => 'ASP'
			)
		)
    ),

	array(
	'id' => 'experience_level',
	'type' => 'dropdown',
	'options' => array(
			'' =>	'-- Select --',
			'0-1 years' => '0-1 years',
			'1-3 years' => '1-3 years',
			'3-5 years' => '3-5 years',
			'5-10 years' => '5-10 years'
		),
	'value'	=>	'0' // Default Option
	),
    array(
        'id' => 'your_self',
        'type' => 'textarea',
        'placeholder' => 'Tel about ur self',
        'label' => 'Tel about ur self'
    ),
    
	
	

	 array(
       'id' => 'submit',
       'type' => 'submit',
       'name' => 'submit'
    ),
        
	);
	

$form->rule('required', ['first_name','last_name', 'address', 'confirm_email', 'email', 'phone_number'])->message('Required: {field} cannot be empty');
$form->rule('email', 'email');
$form->rule('equals','confirm_email','email');
echo $form->_form_open();
echo $form->form_field_creation($form_options);
echo $form->_form_close();

?>