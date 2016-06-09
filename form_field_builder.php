<?php
Class Sample_Form_Creator{
	
	private $config = array(/* Config array - can be overrided by passing in array in ini() */
        'default_input_type' => 'form_input'
    );
    private $func; /* Global function holder - used in switches */
	private $print_string = ''; /* An output buffer */
	private $data_source; /* Global holder for the source of the data */
    private $elm_options; /* Global options holder */
	
	const ERROR_DEFAULT = 'Invalid';
    protected $_fields = array();
    protected $_errors = array();
    protected $_validations = array();
    protected $_labels = array();
    protected static $_rules = array();
    protected static $_ruleMessages = array();
    protected $validUrlPrefixes = array('http://', 'https://', 'ftp://');

	public function __construct($data = array(), $fields = array()){
		if(empty($data)){
			$data = $_REQUEST;
		}
        $this->_fields = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data;
		
		// print_r($this->_fields);
	}
	
	/**
	 * Text Input Field
	 *
	 * @param	mixed
	 * @param	string
	 * @param	mixed
	 * @return	string
	 */	
	function form_input($data = '', $value = '', $extra = '')
	{
		$defaults = array(
			'type' => 'text',
			'name' => is_array($data) ? '' : $data,
			'value' => $value
		);

		return '<input '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra)." />\n";
		
		
		return '<input '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra)." />\n";
		
	}

	/**
	 * Hidden Input Field
	 *
	 * Generates hidden fields. You can pass a simple key/value string or
	 * an associative array with multiple values.
	 *
	 * @param	mixed	$name		Field name
	 * @param	string	$value		Field value
	 * @param	bool	$recursing
	 * @return	string
	 */	
	function form_hidden($name, $value = '', $recursing = FALSE)
	{
		static $form;

		if ($recursing === FALSE)
		{
			$form = "\n";
		}

		if (is_array($name))
		{
			foreach ($name as $key => $val)
			{
				$this->form_hidden($key, $val, TRUE);
			}

			return $form;
		}

		if ( ! is_array($value))
		{
			$form .= '<input type="hidden" name="'.$name.'" value="'.$this->html_escape($value)."\" />\n";
		}

		return $form;
	}
	
	/**
	 * Drop-down Menu
	 *
	 * @param	mixed	$data
	 * @param	mixed	$options
	 * @param	mixed	$selected
	 * @param	mixed	$extra
	 * @return	string
	 */	 
	function form_dropdown($data = '', $options = array(), $selected = array(), $extra = '')
	{
		$defaults = array();

		if (is_array($data))
		{
			if (isset($data['selected']))
			{
				$selected = $data['selected'];
				unset($data['selected']); // select tags don't have a selected attribute
			}

			if (isset($data['options']))
			{
				$options = $data['options'];
				unset($data['options']); // select tags don't use an options attribute
			}
		}
		else
		{
			$defaults = array('name' => $data);
		}

		is_array($selected) OR $selected = array($selected);
		is_array($options) OR $options = array($options);

		// If no selected state was submitted we will attempt to set it automatically
		if (empty($selected))
		{
			if (is_array($data))
			{
				if (isset($data['name'], $_POST[$data['name']]))
				{
					$selected = array($_POST[$data['name']]);
				}
			}
			elseif (isset($_POST[$data]))
			{
				$selected = array($_POST[$data]);
			}
		}

		$extra = $this->_form_attributes_to_string($extra);

		$multiple = (count($selected) > 1 && stripos($extra, 'multiple') === FALSE) ? ' multiple="multiple"' : '';

		$form = '<select '.rtrim($this->_form_field_attributesl($data, $defaults)).$extra.$multiple.">\n";

		foreach ($options as $key => $val)
		{
			$key = (string) $key;

			if (is_array($val))
			{
				if (empty($val))
				{
					continue;
				}

				$form .= '<optgroup label="'.$key."\">\n";

				foreach ($val as $optgroup_key => $optgroup_val)
				{
					$sel = in_array($optgroup_key, $selected) ? ' selected="selected"' : '';
					$form .= '<option value="'.$this->html_escape($optgroup_key).'"'.$sel.'>'
						.(string) $optgroup_val."</option>\n";
				}

				$form .= "</optgroup>\n";
			}
			else
			{
				$form .= '<option value="'.$this->html_escape($key).'"'
					.(in_array($key, $selected) ? ' selected="selected"' : '').'>'
					.(string) $val."</option>\n";
			}
		}

		return $form."</select>\n";
	}
	
	/**
	 * Checkbox Field
	 *
	 * @param	mixed
	 * @param	string
	 * @param	bool
	 * @param	mixed
	 * @return	string
	 */
	function form_checkbox($data = '', $value = '', $checked = FALSE, $extra = '')
	{
		$defaults = array('type' => 'checkbox', 'name' => ( ! is_array($data) ? $data : ''), 'value' => $value);

		if (is_array($data) && array_key_exists('checked', $data))
		{
			$checked = $data['checked'];

			if ($checked == FALSE)
			{
				unset($data['checked']);
			}
			else
			{
				$data['checked'] = 'checked';
			}
		}

		if ($checked == TRUE)
		{
			$defaults['checked'] = 'checked';
		}
		else
		{
			unset($defaults['checked']);
		}

		return '<input '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra)." />\n";
	}
	
	/**
	 * Set Radio
	 *
	 * Let's you set the selected value of a radio field via info in the POST array.
	 * If Form Validation is active it retrieves the info from the validation class
	 *
	 * @param	string	$field
	 * @param	string	$value
	 * @param	bool	$default
	 * @return	string
	 */
	function form_radio($data = '', $value = '', $checked = FALSE, $extra = '')
	{
		$defaults = array('type' => 'radio', 'name' => ( ! is_array($data) ? $data : ''), 'value' => $value);

		if (is_array($data) && array_key_exists('checked', $data))
		{
			$checked = $data['checked'];

			if ($checked == FALSE)
			{
				unset($data['checked']);
			}
			else
			{
				$data['checked'] = 'checked';
			}
		}

		if ($checked == TRUE)
		{
			$defaults['checked'] = 'checked';
		}
		else
		{
			unset($defaults['checked']);
		}

		return '<input '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra)." />\n";
	}

	/**
	 * Textarea field
	 *
	 * @param	mixed	$data
	 * @param	string	$value
	 * @param	mixed	$extra
	 * @return	string
	 */	
	function form_textarea($data = '', $value = '', $extra = '')
	{
		$defaults = array(
			'name' => is_array($data) ? '' : $data,
			'cols' => '40',
			'rows' => '10'
		);

		if ( ! is_array($data) OR ! isset($data['value']))
		{
			$val = $value;
		}
		else
		{
			$val = $data['value'];
			unset($data['value']); // textareas don't use the value attribute
		}

		return '<textarea '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra).'>'
			.$this->html_escape($val)
			."</textarea>\n";
	}
	
	/**
	 * Form Label Tag
	 *
	 * @param	string	The text to appear onscreen
	 * @param	string	The id the label applies to
	 * @param	string	Additional attributes
	 * @return	string
	 */
	function form_label($label_text = '', $id = '', $attributes = array())
	{

		$label = '<label';

		if ($id !== '')
		{
			$label .= ' for="'.$id.'"';
		}

		if (is_array($attributes) && count($attributes) > 0)
		{
			foreach ($attributes as $key => $val)
			{
				$label .= ' '.$key.'="'.$val.'"';
			}
		}

		return $label.'>'.$label_text.'</label>';
	}
	
	/**
	 * Submit Button
	 *
	 * @param	mixed
	 * @param	string
	 * @param	mixed
	 * @return	string
	 */
	function form_submit($data = '', $value = '', $extra = '')
	{
		$defaults = array(
			'type' => 'submit',
			'name' => is_array($data) ? '' : $data,
			'value' => $value
		);

		return '<input '.$this->_form_field_attributesl($data, $defaults).$this->_form_attributes_to_string($extra)." />\n";
	}
	
	/**
	 * Form Declaration
	 *
	 * Creates the opening portion of the form.
	 *
	 * @param	string	the URI segments of the form destination
	 * @param	array	a key/value pair of attributes
	 * @param	array	a key/value pair hidden data
	 * @return	string
	 */
	function _form_open($action = '', $attributes = array(), $hidden = array())
	{

		// If no action is provided then set to the current url
		if ( ! $action)
		{
			$action = $this->base_url();
		}
		

		$attributes = $this->_form_attributes_to_string($attributes);

		if (stripos($attributes, 'method=') === FALSE)
		{
			$attributes .= ' method="post"';
		}

		if (stripos($attributes, 'accept-charset=') === FALSE)
		{
			$attributes .= ' accept-charset="'.strtolower('charset').'"';
		}

		$form = '<form action="'.$action.'"'.$attributes.">\n";

		return $form;
	}
	
	/**
	 * Form Close Tag
	 *
	 * @param	string
	 * @return	string
	 */
	function _form_close($extra = '')
	{
		return '</form>'.$extra;
	}
	
	/**
	 * Anchor Link
	 *
	 * Creates an anchor based on the local URL.
	 *
	 * @param	string	the URL
	 * @param	string	the link title
	 * @param	mixed	any attributes
	 * @return	string
	 */
	function anchor($uri = '', $title = '', $attributes = '')
	{
		$title = (string) $title;

		$site_url = is_array($uri)
			? ($uri)
			: (preg_match('#^(\w+:)?//#i', $uri) ? $uri : ($uri));

		if ($title === '')
		{
			$title = $site_url;
		}

		if ($attributes !== '')
		{
			$attributes = $this->_form_attributes_to_string($attributes);
		}

		return '<a href="'.$site_url.'"'.$attributes.'>'.$title.'</a>';
	}
	
	private function _label() {
        $label = '';
        if (isset($this->elm_options['label']) && $this->elm_options['label'] == 'none') {
            return ''; /* the keyword none */
        } else if (isset($this->elm_options['label'])) {
            $label = $this->elm_options['label'];
        } elseif (isset($this->elm_options['id']) && $this->func != 'form_submit') {
            $label = $this->_make_field_label($this->elm_options['id']);
        }

        if ($this->func == 'form_submit') {
            $label = '';
        }

        return $this->form_label($label, $this->elm_options['name'], array(
            'class' => ''
        ));
    }

    private function _make_field_label($str) {
        return ucwords(str_replace(array('_', '-', '[', ']'), array(' ', ' ', ' ', ' '), $str));
    }
	
	private function _reset_field_builder() {
        $this->print_string = '';
    }
	
	/**
	 * build_form_field_input function builds all the form options(input types like select, text, radio, checkbox etc.,) 
	 *
	 */
	
	function form_field_creation($options, $data_source = array()) {
        $this->_reset_field_builder();
        $this->data_source = (array) $data_source;
		$this->validate();
		if(!$this->errors() && $_SERVER['REQUEST_METHOD'] == 'POST'){
			$this->print_string .= 'Form Submitted Successfully';
		}
        foreach ($options as $elm_options) {
            $this->elm_options = $elm_options;
	
            if (is_array($this->elm_options)) {
				if(isset($this->elm_options['name'])){
					$name_val = $this->elm_options['name'];
				}else{
					$name_val = $this->elm_options['id'];
				}
				$this->print_string .= '<div class="field_container '.$name_val.'">';
                $this->_form_field_prep_options();
                switch ($this->func) {
                    case 'form_hidden':
                        $this->print_string .= $this->_build_form_field_input();
                        break;
                    case 'form_checkbox':
                    case 'form_radio':
                        $id = $this->elm_options['id'];
                        $this->elm_options['id'] = '';
						
                        $this->print_string .= $this->_label();

                        $this->elm_options['id'] = $id;
                        $all_elm_options = $this->elm_options;

                        foreach ($all_elm_options['options'] as $elm_suboptions) {
                            $this->elm_options = $elm_suboptions;
                            $this->elm_options['name'] = $all_elm_options['name'];
                            $this->elm_options['id'] = $all_elm_options['id'];

                            // Set value as label if no label is set
                            array_key_exists('label', $this->elm_options) || $this->elm_options['label'] = $this->elm_options['value'];

                            $label_class = substr($this->func, 5).'-inline';
                            array_key_exists('disabled', $this->elm_options) && $label_class .= ' disabled';

                            $this->print_string .= '<label class="'.$label_class.'">';
                            $this->print_string .= $this->_build_form_field_input(FALSE);
                            $this->print_string .= $this->elm_options['label'].'</label>';
                        }
                        $this->elm_options = $all_elm_options;
						if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($this->func == 'form_checkbox' || $this->func == 'form_radio')){
							if(isset($this->elm_options['name'])){
								$name_val = $this->elm_options['name'];
							}else{
								$name_val = $this->elm_options['id'];
							}
							foreach($this->errors() as $key=>$value){
								if($name_val == $key){
									$this->print_string .= '<span class="error">'.($value[0]).'</span>';
								}
							}
						}
                        break;
                    default:
                        $this->print_string .= $this->_label();
                        $this->print_string .= $this->_build_form_field_input();
						if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($this->func == 'form_checkbox' || $this->func == 'form_radio')){
							if(isset($this->elm_options['name'])){
								$name_val = $this->elm_options['name'];
							}else{
								$name_val = $this->elm_options['id'];
							}
							foreach($this->errors() as $key=>$value){
								if($name_val == $key){
									$this->print_string .= '<span class="error">'.($value[0]).'</span>';
								}
							}
						}
                        break;
                }
				$this->print_string .= '</div>';
            }
        }
        return $this->squish_HTML($this->print_string);
    }


	
	private function _build_form_field_input($include_pre_post = true)
	{
        $input_html_string = '';
        
        if ($this->func == 'form_combine') {
            if (!isset($this->elm_options['elements'])) {
                // dump($this->elm_options);
                // show_error('Tried to create `field_combine` with no elements. (id="' . $this->elm_options['name'] . '")');
            }

            $elm_options_backup = $this->elm_options;

            $counter = 0;
            foreach ($elm_options_backup['elements'] as $elm) {
                $this->elm_options = $elm; /* We override elm_options */
                $this->_form_field_prep_options(); /* Run Prep on the new one */
                if ($counter > 0 && !empty($elm_options_backup['combine_divider'])) {
                    $input_html_string .= $elm_options_backup['combine_divider'];
                }
                $input_html_string .= $this->_build_form_field_input(false);
                $counter++;
            }

            $this->elm_options = $elm_options_backup; /* We put our options back */
            $this->_form_field_prep_options(); /* Run Prep to restore the state in which we begain */
        } else {
            
            switch ($this->func) {
            
                case 'form_button':
                case 'form_anchor':
                case 'form_a':
                
                    $value = $this->elm_options['label'];
                    unset($this->elm_options['label']);

                    $input_html_string = $this->anchor('', $value, $this->elm_options);
                    break;
                case 'form_label':
                    $input_html_string = $this->form_label($this->_make_field_label($this->elm_options['value']), '', array(
                        'class' => 'control-label text-left'
                    ));
                    break;
                case 'form_email':
                    $this->elm_options['type'] = 'email';
                    $input_html_string = $this->form_input($this->elm_options);
                    break;
                case 'form_tel':
                    $this->elm_options['type'] = 'tel';
                    $input_html_string = $this->form_input($this->elm_options);
                    break;
                case 'form_number':
                    $this->elm_options['type'] = 'number';
                    $input_html_string = $this->form_input($this->elm_options);
                    break;
                case 'form_input':
                    $input_html_string = $this->form_input($this->elm_options);
                    break;
                case 'form_hidden':
                    return field_hidden($this->elm_options['id'], $this->elm_options['value']);
                case 'form_submit':
                    $name = $this->elm_options['id'];
                    $label = $this->_make_field_label((isset($this->elm_options['label']) ? $this->elm_options['label'] : $this->elm_options['id']));

                    unset($this->elm_options['id']);
                    unset($this->elm_options['label']);

                    $input_html_string = $this->form_submit($name, $label, $this->_form_field_create_extra_string($this->elm_options));
                    break;
                case 'form_option':
                case 'form_dropdown':
                    /* form_dropdown is different than an input */
                    if (isset($this->elm_options['options']) && !empty($this->elm_options['options'])) {
                        $name = $this->elm_options['name'];
                        $options = $this->elm_options['options'];
                        $value = $this->elm_options['value'];

                        unset($this->elm_options['name']);
                        unset($this->elm_options['value']);
                        unset($this->elm_options['options']);

                        $input_html_string = $this->form_dropdown($name, $options, $value, $this->_form_field_create_extra_string());
                    } else {
                        // dump($this->elm_options);
                        // show_error('Tried to create `field_dropdown` with no options. (id="' . $this->elm_options['name'] . '")');
                    }
                    break;
                case 'form_textarea':
                    $this->elm_options['value'] = html_entity_decode($this->elm_options['value']);
                    $input_html_string = $this->form_textarea($this->elm_options);
                    break;
                case 'form_checkbox':
                    $input_html_string = $this->form_checkbox($this->elm_options);
                    break;
                case 'form_radio':
                    $input_html_string = $this->form_radio($this->elm_options);
                    break;
				
                default:
                    if (function_exists($this->func)) {
                        $input_html_string = call_user_func($this->func, $this->elm_options);
                    } else {
                        // show_error("Could not find function to build form element: '{$this->func}'");
                    }
                    break;
            }
        }
        $ret_string = '';
        $ret_string .= (empty($input_html_string)) ? '' : $input_html_string;
	
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->func != 'field_checkbox' && $this->func != 'field_radio'){
			if(isset($this->elm_options['name'])){
				$name_val = $this->elm_options['name'];
			}else{
				$name_val = $this->elm_options['id'];
			}
			foreach($this->errors() as $key=>$value){
				if($name_val == $key){
					$ret_string .= '<span class="error">'.($value[0]).'</span>';
				}
			}
		}

        return $ret_string;
    }
	
	/* added for dropdown */
	private function _form_field_create_extra_string() {
        $extra = '';
        foreach ($this->elm_options as $k => $v) {
            $extra .= " {$k}=\"{$v}\"";
        }
        return trim($extra);
    }
	
	private function _form_field_prep_options() {
        foreach ($this->elm_options as &$opt) {
            /* trying again to change everything to an array */
            if (is_object($opt)) {
                $opt = (array) $opt;
            }
        }
        $this->func = $this->config['default_input_type'];
        /* Pull the input type from the array */
        if (isset($this->elm_options['type']) && !empty($this->elm_options['type'])) {
            $this->func = 'form_' . $this->elm_options['type'];
            unset($this->elm_options['type']);
        } else {
            $this->func = $this->config['default_input_type'];
        }
		
		/* make sure there is a name' attribute */
        if (!isset($this->elm_options['name'])) {
            /* put the id as the name by default - makes smaller 'config' arrays */
            if (isset($this->elm_options['id'])) {
                $this->elm_options['name'] = $this->elm_options['id'];
            } else {
                $this->elm_options['name'] = '';
            }
        }
		
		return;
	}
	
	function _form_field_attributesl($attributes, $default)
	{
		if (is_array($attributes))
		{
			foreach ($default as $key => $val)
			{
				if (isset($attributes[$key]))
				{
					$default[$key] = $attributes[$key];
					unset($attributes[$key]);
				}
			}

			if (count($attributes) > 0)
			{
				$default = array_merge($default, $attributes);
			}
		}

		$att = '';

		foreach ($default as $key => $val)
		{
			if ($key === 'value')
			{
				$val = $this->html_escape($val);
			}
			elseif ($key === 'name' && ! strlen($default['name']))
			{
				continue;
			}

			$att .= $key.'="'.$val.'" ';
		}

		return $att;
	}

	function _form_attributes_to_string($attributes)
	{
		if (empty($attributes))
		{
			return '';
		}

		if (is_object($attributes))
		{
			$attributes = (array) $attributes;
		}

		if (is_array($attributes))
		{
			$atts = '';

			foreach ($attributes as $key => $val)
			{
				$atts .= ' '.$key.'="'.$val.'"';
			}

			return $atts;
		}

		if (is_string($attributes))
		{
			return ' '.$attributes;
		}

		return FALSE;
	}
	
	function squish_HTML($html) {
        $re = '%# Collapse whitespace everywhere but in blacklisted elements.
        (?>             # Match all whitespans other than single space.
            [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
        | \s{2,}        # or two or more consecutive-any-whitespace.
        ) # Note: The remaining regex consumes no text at all...
        (?=             # Ensure we are not in a blacklist tag.
            [^<]*+        # Either zero or more non-"<" {normal*}
            (?:           # Begin {(special normal*)*} construct
                <           # or a < starting a non-blacklist tag.
                (?!/?(?:textarea|pre|script)\b)
                [^<]*+      # more non-"<" {normal*}
            )*+           # Finish "unrolling-the-loop"
            (?:           # Begin alternation group.
                <           # Either a blacklist start tag.
                (?>textarea|pre|script)\b
            | \z          # or end of file.
            )             # End alternation group.
        ) # If we made it here, we are not in a blacklist tag.
        %Six';
        $text = preg_replace($re, " ", $html);
        if ($text === null) {
            return $html;
        }
        return $text;
    }
	
	function base_url(){
	  return sprintf(
		"%s://%s%s",
		isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
		$_SERVER['HTTP_HOST'],
		$_SERVER['REQUEST_URI']
	  );
	}
	
	function html_escape($var)
	{
		if (empty($var))
		{
			return $var;
		}
		return htmlspecialchars($var, ENT_QUOTES);
	}
	
	/**
     * Required field validator
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateRequired($field, $value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }

        return true;
    }

    /**
     * Validate that two values match
     *
     * @param  string $field
     * @param  mixed  $value
     * @param  array  $params
     * @internal param array $fields
     * @return bool
     */
    protected function validateEquals($field, $value, array $params)
    {
        $field2 = $params[0];

        return isset($this->_fields[$field2]) && $value == $this->_fields[$field2];
    }

    /**
     * Validate that a field is numeric
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateNumeric($field, $value)
    {
        return is_numeric($value);
    }

    /**
     * Validate the length of a string (min)
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $params
     *
     * @return boolean
     */
    protected function validateLengthMin($field, $value, $params)
    {
        $length = $this->stringLength($value);

        return ($length !== false) && $length >= $params[0];
    }

    /**
     * Validate the length of a string (max)
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $params
     *
     * @return boolean
     */
    protected function validateLengthMax($field, $value, $params)
    {
        $length = $this->stringLength($value);

        return ($length !== false) && $length <= $params[0];
    }

    /**
     * Get the length of a string
     *
     * @param  string $value
     * @return int|false
     */
    protected function stringLength($value)
    {
        if (!is_string($value)) {
            return false;
        } elseif (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    /**
     * Validate the size of a field is greater than a minimum value.
     *
     * @param  string $field
     * @param  mixed  $value
     * @param  array  $params
     * @internal param array $fields
     * @return bool
     */
    protected function validateMin($field, $value, $params)
    {
        if (!is_numeric($value)) {
            return false;
        } elseif (function_exists('bccomp')) {
            return !(bccomp($params[0], $value, 14) === 1);
        } else {
            return $params[0] <= $value;
        }
    }

    /**
     * Validate the size of a field is less than a maximum value
     *
     * @param  string $field
     * @param  mixed  $value
     * @param  array  $params
     * @internal param array $fields
     * @return bool
     */
    protected function validateMax($field, $value, $params)
    {
        if (!is_numeric($value)) {
            return false;
        } elseif (function_exists('bccomp')) {
            return !(bccomp($value, $params[0], 14) === 1);
        } else {
            return $params[0] >= $value;
        }
    }

    /**
     * Validate that a field is a valid e-mail address
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateEmail($field, $value)
    {
		return filter_var($value, \FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate that a field is a valid URL by syntax
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateUrl($field, $value)
    {
        foreach ($this->validUrlPrefixes as $prefix) {
            if (strpos($value, $prefix) !== false) {
                return filter_var($value, \FILTER_VALIDATE_URL) !== false;
            }
        }

        return false;
    }

    /**
     * Validate that a field contains only alphabetic characters
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateAlpha($field, $value)
    {
        return preg_match('/^([a-z])+$/i', $value);
    }

    /**
     * Validate that a field contains only alpha-numeric characters
     *
     * @param  string $field
     * @param  mixed  $value
     * @return bool
     */
    protected function validateAlphaNum($field, $value)
    {
        return preg_match('/^([a-z0-9])+$/i', $value);
    }

    /**
     * Validate that a field passes a regular expression check
     *
     * @param  string $field
     * @param  mixed  $value
     * @param  array  $params
     * @return bool
     */
    protected function validateRegex($field, $value, $params)
    {
        return preg_match($params[0], $value);
    }


    /**
     *  Get array of fields and data
     *
     * @return array
     */
    public function data()
    {
        return $this->_fields;
    }

    /**
     * Get array of error messages
     *
     * @param  null|string $field
     * @return array|bool
     */
    public function errors($field = null)
    {
        if ($field !== null) {
            return isset($this->_errors[$field]) ? $this->_errors[$field] : false;
        }

        return $this->_errors;
    }

    /**
     * Add an error to error messages array
     *
     * @param string $field
     * @param string $msg
     * @param array  $params
     */
    public function error($field, $msg, array $params = array())
    {
        $msg = $this->checkAndSetLabel($field, $msg, $params);

        $values = array();
        // Printed values need to be in string format
        foreach ($params as $param) {
            if (is_array($param)) {
                $param = "['" . implode("', '", $param) . "']";
            }
            if ($param instanceof \DateTime) {
                $param = $param->format('Y-m-d');
            } else {
                if (is_object($param)) {
                    $param = get_class($param);
                }
            }
            // Use custom label instead of field name if set
            if (is_string($params[0])) {
                if (isset($this->_labels[$param])) {
                    $param = $this->_labels[$param];
                }
            }
            $values[] = $param;
        }

        $this->_errors[$field][] = vsprintf($msg, $values);
    }

    /**
     * Specify validation message to use for error for the last validation rule
     *
     * @param  string $msg
     * @return $this
     */
    public function message($msg)
    {
        $this->_validations[count($this->_validations) - 1]['message'] = $msg;

        return $this;
    }

    /**
     * Reset object properties
     */
    public function reset()
    {
        $this->_fields = array();
        $this->_errors = array();
        $this->_validations = array();
        $this->_labels = array();
    }

    protected function getPart($data, $identifiers)
    {
        // Catches the case where the field is an array of discrete values
        if (is_array($identifiers) && count($identifiers) === 0) {
            return array($data, false);
        }

        $identifier = array_shift($identifiers);

        // Glob match
        if ($identifier === '*') {
            $values = array();
            foreach ($data as $row) {
                list($value, $multiple) = $this->getPart($row, $identifiers);
                if ($multiple) {
                    $values = array_merge($values, $value);
                } else {
                    $values[] = $value;
                }
            }

            return array($values, true);
        }

        // Dead end, abort
        elseif ($identifier === NULL || ! isset($data[$identifier])) {
            return array(null, false);
        }

        // Match array element
        elseif (count($identifiers) === 0) {
            return array($data[$identifier], false);
        }

        // We need to go deeper
        else {
            return $this->getPart($data[$identifier], $identifiers);
        }
    }

    /**
     * Run validations and return boolean result
     *
     * @return boolean
     */
    public function validate()
    {
        foreach ($this->_validations as $v) {
            foreach ($v['fields'] as $field) {
                 list($values, $multiple) = $this->getPart($this->_fields, explode('.', $field));

                // Don't validate if the field is not required and the value is empty
                if ($this->hasRule('optional', $field) && isset($values)) {
                    //Continue with execution below if statement
                } elseif ($v['rule'] !== 'required' && !$this->hasRule('required', $field) && (! isset($values) || $values === '' || ($multiple && count($values) == 0))) {
                    continue;
                }

                // Callback is user-specified or assumed method on class
                if (isset(static::$_rules[$v['rule']])) {
                    $callback = static::$_rules[$v['rule']];
                } else {
                    $callback = array($this, 'validate' . ucfirst($v['rule']));
                }

                if (!$multiple) {
                    $values = array($values);
                }

                $result = true;
                foreach ($values as $value) {
                    $result = $result && call_user_func($callback, $field, $value, $v['params'], $this->_fields);
                }

                if (!$result) {
                    $this->error($field, $v['message'], $v['params']);
                }
            }
        }

        return count($this->errors()) === 0;
    }

    /**
     * Determine whether a field is being validated by the given rule.
     *
     * @param  string  $name  The name of the rule
     * @param  string  $field The name of the field
     * @return boolean
     */
    protected function hasRule($name, $field)
    {
        foreach ($this->_validations as $validation) {
            if ($validation['rule'] == $name) {
                if (in_array($field, $validation['fields'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Register new validation rule callback
     *
     * @param  string                    $name
     * @param  mixed                     $callback
     * @param  string                    $message
     * @throws \InvalidArgumentException
     */
    public static function addRule($name, $callback, $message = self::ERROR_DEFAULT)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Second argument must be a valid callback. Given argument was not callable.');
        }

        static::$_rules[$name] = $callback;
        static::$_ruleMessages[$name] = $message;
    }

    /**
     * Convenience method to add a single validation rule
     *
     * @param  string                    $rule
     * @param  array                     $fields
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function rule($rule, $fields)
    {
        if (!isset(static::$_rules[$rule])) {
            $ruleMethod = 'validate' . ucfirst($rule);
			// var_dump(!method_exists($this, $ruleMethod));
            if (!method_exists($this, $ruleMethod)) {
                throw new \InvalidArgumentException("Rule '" . $rule . "' has not been registered with " . __CLASS__ . "::addRule().");
            }
        }

        // Ensure rule has an accompanying message
        $message = isset(static::$_ruleMessages[$rule]) ? static::$_ruleMessages[$rule] : self::ERROR_DEFAULT;

        // Get any other arguments passed to function
        $params = array_slice(func_get_args(), 2);

        $this->_validations[] = array(
            'rule' => $rule,
            'fields' => (array) $fields,
            'params' => (array) $params,
            'message' => '{field} ' . $message
        );

        return $this;
    }

    /**
     * @param  string $value
     * @internal param array $labels
     * @return $this
     */
    public function label($value)
    {
        $lastRules = $this->_validations[count($this->_validations) - 1]['fields'];
        $this->labels(array($lastRules[0] => $value));

        return $this;
    }

    /**
     * @param  array  $labels
     * @return string
     */
    public function labels($labels = array())
    {
        $this->_labels = array_merge($this->_labels, $labels);

        return $this;
    }

    /**
     * @param  string $field
     * @param  string $msg
     * @param  array  $params
     * @return array
     */
    protected function checkAndSetLabel($field, $msg, $params)
    {
        if (isset($this->_labels[$field])) {
            $msg = str_replace('{field}', $this->_labels[$field], $msg);

            if (is_array($params)) {
                $i = 1;
                foreach ($params as $k => $v) {
                    $tag = '{field'. $i .'}';
                    $label = isset($params[$k]) && (is_numeric($params[$k]) || is_string($params[$k])) && isset($this->_labels[$params[$k]]) ? $this->_labels[$params[$k]] : $tag;
                    $msg = str_replace($tag, $label, $msg);
                    $i++;
                }
            }
        } else {
            $msg = str_replace('{field}', ucwords(str_replace('_', ' ', $field)), $msg);
        }

        return $msg;
    }

    /**
     * Convenience method to add multiple validation rules with an array
     *
     * @param array $rules
     */
    public function rules($rules)
    {
        foreach ($rules as $ruleType => $params) {
            if (is_array($params)) {
                foreach ($params as $innerParams) {
                    array_unshift($innerParams, $ruleType);
                    call_user_func_array(array($this, 'rule'), $innerParams);
                }
            } else {
                $this->rule($ruleType, $params);
            }
        }
    }
}

?>