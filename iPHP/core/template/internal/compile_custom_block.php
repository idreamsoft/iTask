<?php
/**
 * Template Lite compile custom block - template internal module
 *
 * Type:	 template
 * Name:	 compile_custom_block
 */

function compile_custom_block($function, $modifiers, $arguments, &$_result, &$object){
	if ($function[0] == '/'){
		$start_tag = false;
		$function = substr($function, 1);
	}else{
		$start_tag = true;
	}

	if ($function = $object->_plugin_exists($function, "block")){
		if ($start_tag){
			$_args = $object->_parse_arguments($arguments);
			foreach($_args as $key => $value){
				if (is_bool($value)){
					$value = $value ? 'true' : 'false';
				}
				if (is_null($value)){
					$value = 'null';
				}
				$_args[$key] = "'$key' => $value";
			}
			$_result = "<?php \$this->_tag_stack[] = array('$function', array(".implode(',', (array)$_args).")); ";
			$_result .= '$_block_content = '.$function . '(array(' . implode(',', (array)$_args) .'), null, $this); ';
			$_result .= 'if(!$_block_content){';
			$_result .= 'ob_start(); ?>';
		}else{
			$_result .= '<?php $this->_block_content = ob_get_contents(); ob_end_clean(); ';
			$_result .= 'if($_block_content===null){ $_block_content = $this->_block_content; } ';
			$_result .= $function . '($this->_tag_stack[count($this->_tag_stack) - 1][1], $this->_block_content, $this); ';
			$modifiers && $_result .= '$this->_block_content = ' . $object->_parse_modifier('$this->_block_content', $modifiers) . '; ';
			$_result .= '} ';
			$_result .= 'echo $_block_content; array_pop($this->_tag_stack); ?>';
		}
		return true;
	}else{
		return false;
	}
}
