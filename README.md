# Dynamic-Form-Generator-in-PHP
Its easy and simple to use all HTML input fields with validations using php.

## Usage

```
Plugin is used to create form fields and validations for all the inputs.
Ex: confirm email, required, email, minlength, maxlength etc..
```

## Setup

you must include form_field_builder.php in index.php, where you want to use this form generation code.

## Implementing of Created Form Builder

when you are initializing a sample test_file.php to check. you must use $form = new Sample_Form_Creator(); in test_file.php code.

````
$form->rule('required', ['first_name','last_name', 'address', 'confirm_email', 'email', 'phone_number'])->message('Required: {field} cannot be empty');
$form->rule('email', 'email');
$form->rule('equals','confirm_email','email');
echo $form->_form_open();
echo $form->form_field_creation($form_options);
echo $form->_form_close();
````

## Testing

```
push the codes in wamp or xampp to play around and run the tests.

```

## Suggestions

```
Please let me know any comments. Thanks ;)
```