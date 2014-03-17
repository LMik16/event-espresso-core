<?php

/**
 * For representing a single form input. Extends EE_Form_SEciton_Base because
 * it is a part of a form and shares a suprisingly large amount of functionality
 */
abstract class EE_Form_Input_Base extends EE_Form_Section_Base{
	
	/**
	 * the input's name attribute
	 * @var string
	 */
	protected $_html_name;
	/**
	 * id for the html label tag
	 * @var string
	 */
	protected $_html_label_id;
	/**
	 * class for teh html label tag
	 * @var string
	 */
	protected $_html_label_class;
	/**
	 * style for teh html label tag
	 * @var string
	 */
	protected $_html_label_style;
	/**
	 * text to be placed in the html label
	 * @var string
	 */
	protected $_html_label_text;
	/**
	 * the full html label. If used, all other html_label_* properties are invalid
	 * @var string
	 */
	protected $_html_label;
	
	protected $_sanitized_value;
	protected $_normalized_value;
	
	/**
	 * Strategy used for displaying this field.
	 * Child classes must use _get_display_strategy to access it.
	 * @var EE_Display_Strategy_Base
	 */
	private $_display_strategy;
	
	/**
	 * Gets all the validation strategies used on this field
	 * @var EE_Validation_Strategy_Base[]
	 */
	private $_validation_strategies;
	
	/**
	 * The normalization strategy for this field
	 * @var EE_Validation_Strategy_Base
	 */
	private $_normalization_strategy;
	
	public function __construct($options_array = array()){
		if(isset($options_array['html_name'])){
			$this->_html_name = $options_array['html_name'];
		}
		if(isset($options_array['html_label_id'])){
			$this->_html_label_id = $options_array['html_label_id'];
		}
		if(isset($options_array['html_label_class'])){
			$this->_html_label_class = $options_array['html_label_class'];
		}
		if(isset($options_array['html_label_style'])){
			$this->_html_label_style = $options_array['html_label_style'];
		}
		if(isset($options_array['html_label_text'])){
			$this->_html_label_text = $options_array['html_label_text'];
		}
		if(isset($options_array['html_label'])){
			$this->_html_label = $options_array['html_label'];
		}
		if(isset($options_array['default'])){
			$this->_sanitized_value = $options_array['default'];
		}
		if(isset($options_array['required']) && in_array($options_array['required'], array('true',true))){
			$this->_add_validation_strategy(new EE_Required_Validation_Strategy());
		}
		
		
		
		
		$this->_display_strategy->_construct_finalize($this);
		if($this->_validation_strategies){
			foreach($this->_validation_strategies as $validation_strategy){
				$validation_strategy->_construct_finalize($this);
			}
		}
		$this->_normalization_strategy->_construct_finalize($this);
		
		parent::__construct($options_array);
	}
	
	/**
	 * Sets the html_name to its dfeautl value, if none was specified in teh constructor.
	 * Calcuation involves using hte name and the parent's html_name
	 */
	protected function _set_default_html_name_if_empty(){
		if( ! $this->_html_name){
			if( $this->_parent_section && $this->_parent_section instanceof EE_Form_Section_Proper){
				$this->_html_name = $this->_parent_section->name() . "[{$this->_name}]";
			}else{
				$this->_html_name = $this->_name;
			}
		}
	}
	
	function _construct_finalize($parent_form_section, $name) {
		parent::_construct_finalize($parent_form_section, $name);
		$this->_set_default_html_name_if_empty();
		if( ! $this->_html_label ){
			if( ! $this->_html_label_text){
				$this->_html_label_text = ucwords( str_replace("_"," ",$name));
			}
		}
	}
	
	 /**
	  * Returns the strategy for displaying this form input. If none is set, throws an exception.
	  * @return EE_Display_Strategy_Base
	  * @throws EE_Error
	  */
	protected function _get_display_strategy(){
		if( ! $this->_display_strategy || ! $this->_display_strategy instanceof EE_Display_Strategy_Base){
			throw new EE_Error(sprintf(__("Cannot get display strategy for form input with name %s and id %s, because it has not been set in the constructor", "event_espresso"),$this->html_name(),$this->html_id()));
		}else{
			return $this->_display_strategy;
		}
	}
	/**
	 * Sets the display strategy. 
	 * @param EE_Display_Strategy_Base $strategy
	 */
	protected function _set_display_strategy(EE_Display_Strategy_Base $strategy){
		$this->_display_strategy = $strategy;
	}
	
	/**
	 * Sets the sanitization strategy
	 * @param EE_Normalization_Strategy_Base $strategy
	 */
	protected function _set_normalization_strategy(EE_Normalization_Strategy_Base $strategy){
		$this->_normalization_strategy = $strategy;
	}
	
	/**
	 * Returns all teh validation strategies which apply to this field, numerically indexed
	 * @return EE_Validation_Strategy_Base[]
	 */
	public function get_validation_strategies(){
		if(is_array($this->_validation_strategies)){
			return $this->_validation_strategies;
		}else{
			return array();
		}
		
	}
	/**
	 * Adds this strategy to the field so it will be used in both JS validation and server-side validation
	 * @param EE_Validation_Strategy_Base $validation_strategy
	 * @return void
	 */
	protected function _add_validation_strategy(EE_Validation_Strategy_Base $validation_strategy){
		$this->_validation_strategies[] = $validation_strategy;
	}
	/**
	 * Gets the HTML, JS, and CSS necessary to display this field according
	 * to the parent form's layotu strategy
	 * @return string
	 */
	public function get_html(){
		return $this->_parent_section->get_html_for_input($this);
	}
	/**
	 * Gets the HTML for the input itself (no label or errors).
	 * Makes sure the JS and CSS are enqueued for it
	 * @return string
	 */
	public function get_html_for_input(){
		return  $this->_get_display_strategy()->display();
	}
	/**
	 * Gets the HTML for displaying the label
	 * @return string
	 */
	public function get_html_for_label(){
		return $this->_get_display_strategy()->display_label();
	}
	/**
	 * Gets the HTML for dislpaying the errors section
	 * @return string	 
	 */
	public function get_html_for_errors(){
		return $this->_get_display_strategy()->display_errors();
	}
	/**
	 * Validates the input's sanitized value (assumes _sanitize() has already been called)
	 * and returns whether or not the form input's submitted value is value
	 * @return boolean
	 */
	protected function _validate() {
		if(is_array($this->_validation_strategies)){
			foreach($this->_validation_strategies as $validation_strategy){
				try{
				$validation_strategy->validate($this->normalized_value());
				}catch(EE_Validation_Error $e){
					$this->add_validation_error($e);
				}
			}
		}
		if( $this->get_validation_errors()){
			return false;
		}else{
			return true;
		}
	}
	
	
	/**
	 * Picks out the form value that relates to this form input,
	 * and stores it as the sanitized value on the form input, and sets the normalized value.
	 * Returns whether or not any validation errors occurred
	 * @param array $req_data like $_POST
	 * @return boolean whether or not there was an error
	 */
	protected function _normalize($req_data) {
		try{
			$raw_input = $this->find_form_data_for_this_section($req_data);
			//super simple sanitization for now
			if(is_array($raw_input)){
				foreach($raw_input as $key => $value){
					$this->_sanitized_value[$key] = sanitize_text_field($value);
				}
			}else{
				$this->_sanitized_value = sanitize_text_field($raw_input);
			}
			//we want ot mostly leave the input alone in case we need to re-display it to the user
			//but we're just removing anything really nasty
			$this->_normalized_value = $this->_normalization_strategy->normalize($this->sanitized_value());
		}catch(EE_Validation_Error $e){
			$this->add_validation_error($e);
		}
	}
	
	public function html_name(){
		return $this->_html_name;
	}
	
	function html_label_id(){
		return $this->_html_label_id;
	}
	function html_label_class(){
		return $this->_html_label_class;
	}
	function html_label_style(){
		return $this->_html_label_style;
	}
	function html_label_text(){
		return $this->_html_label_text;
	}
	/**
	 * returns the value after it's been cleaned, but it's still a string.
	 * Note, we do not store the exact original value sent in the user's request because
	 * it may have malicious content, and we MIGHT want to store the form input in a transient or something...
	 * in which case, we would have stored the malicious content to our database.
	 * @return string
	 */
	function sanitized_value(){
		return $this->_sanitized_value;
	}
	/**
	 * returns the value after it's been sanitized, and then converted into it's proper type
	 * in PHP. Eg, a string, an int, an array, 
	 * @return mixed
	 */
	function normalized_value(){
		return $this->_normalized_value;
	}
	/**
	 * When generating the JS for the jquery valiation rules like<br>
	 * <code>$( "#myform" ).validate({
		rules: {
		  password: "required",
		  password_again: {
			equalTo: "#password"
		  }
		}
	  });</code>
		if this field had the name 'password_again', it should return 
	 * <br><code>password_again: {
			equalTo: "#password"
		  }</code>
	 * @return array
	 */
	function get_jquery_validation_rules(){
		$jquery_validation_rules = array();
		foreach($this->get_validation_strategies() as $validation_strategy){
			$jquery_validation_rules = array_merge($jquery_validation_rules, $validation_strategy->get_jquery_validation_rule_array());
		}
		if(! empty($jquery_validation_rules)){
			$jquery_validation_js[$this->html_name()] = $jquery_validation_rules;
		}else{
			return array();
		}
		return $jquery_validation_js;
	}
	
	/**
	 * Sets the input's default value for use in displaying in the form (note: does NOT
	 * perform sanitization or normalization on this value, as the programmer should be providing it)
	 * @param mixed $value
	 * @return void
	 */
	function set_default($value){
		$this->_sanitized_value = $value;
	}
	
}
