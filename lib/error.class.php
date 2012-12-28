<?php

	require_once 'defines.inc.php';
	require_once 'table.class.php';

	/*
		This class is used for displaying error messages
	*/	
	class ERROR {
		//holds an instance of LEXICAL class
		private $lexical = null;
		//holds the error count
		private $count = 0;
		//holds the error messages
		private $messages = array(
			ERR_GEN_UND => 'Undefined error',
			ERR_GEN_DEF => 'Unknown error',
			ERR_GEN_EOF => 'Unexpected end of file',
			ERR_LEX_INT => 'Invalid token',
			ERR_SYN_PRG => 'Malformed program structure',
			ERR_SYN_MID => 'Missing identifier',
			ERR_SYN_MPE => 'Missing "."',
			ERR_SYN_MSC => 'Missing ";"',
			ERR_SYN_MRW => 'Missing reserved word',
			ERR_SYN_MBW => 'Missing "begin" word',
			ERR_SYN_MEW => 'Missing "end" word',
			ERR_SYN_MTD => 'Missing ":"',
			ERR_SYN_MVW => 'Missing "var"',
			ERR_SYN_MVT => 'Missing variable type',
			ERR_SYN_MOB => 'Missing "("',
			ERR_SYN_MCB => 'Missing ")"',
			ERR_SYN_MRT => 'Missing relation token',
			ERR_SYN_MAT => 'Missing arithmetic operand',
			ERR_SYN_MCA => 'Missing identifier, number or expression',
			ERR_SYN_CBO => 'Comment block not closed',
			ERR_SEM_ADI => 'Identifier already defined in current scope',
			ERR_SEM_UDI => 'Undefined identifier in current scope',
			ERR_SEM_IVT => 'Incompatible variable type',
			ERR_SEM_PNA => 'Procedure expects no arguments',
			ERR_SEM_PMA => 'Procedure received more arguments than expected',
			ERR_SEM_PLA => 'Procedure received less arguments than expected',
			ERR_SEM_MAR => 'Procedure expects arguments, but none found',
			ERR_SEM_ANV => 'Assingment to something that is not a variable or parameter',
			ERR_SEM_DIV => 'Invalid division, expecting two integers but found real'
		);

		/*
			constructor of class ERROR
		*/
		public function __construct($lexical) {
			if (!is_null($lexical))
				$this->lexical = $lexical;
			else
				die('ERROR::__construct() parameter is null!');
		}

		/*
			function that returns a number into hex format using 2 chars
			returns: number in format XX
		*/
		private static function hex($num) {
			$h = strtoupper(dechex($num));
			if ((strlen($h) % 2) != 0)
				return '0'.$h;
			else
				return $h;
		}

		/*
			function that checks if the given chain/token are present into the given table
			returns: true if found; false else.
		*/
		private static function check($table = array(), $chain, $token) {
			foreach($table as $item) {
				if ((isset($item['chain'])) && ($item['chain'] == $chain))
					return true;
				else if ((isset($item['token'])) && ($item['token'] == $token))
					return true;
			}
			return false;
		}

		/*
			property for $count
		*/
		public function count() {
			return $this->count;
		}

		/*
			function that shows a pre-configured error message and finds next sync token (panic mode)
		*/
		public function show($type = ERT_WAR, $num = ERR_GEN_DEF, $line = -1, $expect = '', &$chain, &$token, $tableSync = array()) {
			//incrementing the error counter
			$this->count++;
			//prints the error type (warning, error or fatal error)
			switch ($type) {
				case ERT_WAR:
					echo '[w] ';
					break;
				case ERT_ERR:
					echo '[e] ';
					break;
				case ERT_FAT:
					echo '[f] ';
			}
			//prints the error number
			echo '0x'.ERROR::hex($num).': ';
			//prints the error message
			if (isset($this->messages[$num]))
				echo $this->messages[$num];
			else
				echo $this->messages[ERR_GEN_DEF];
			//if the line number is given, show the number
			if ($line != -1)
				echo ' at line '.($line + 1);
			//if the expected chain/token is given, show to user
			if ($expect != '') {
				echo ', expecting "'.$expect.'"';
				//if the founded chain is given, show to user
				if ($chain != '')
					echo ' but found "'.$chain.'".';
				else
					echo '.';
			} else
				//if the next chain is given, show to user so he can see where the error is near to
				if ($chain != '') {
					if (!in_array($num, array(ERR_LEX_INT, ERR_SEM_ADI, ERR_SEM_UDI)))
						echo ', near to "'.$chain.'".';
					else
						echo ': "'.$chain.'".';
				} else
					echo '.';
			echo "\n";
			//if the error type is fatal, terminate the software
			if ($type == ERT_FAT)
				exit(0);
			//searchs for sync tokens
			if ((is_array($tableSync)) && (count($tableSync))) {
				$flag = true;
				while (($flag) && (!ERROR::check($tableSync, $chain, $token))) {
					if ($this->lexical->analysis($chain, $token) == LEXICAL_EOF)
						$flag = false;
					echo 'panic: '.$chain."\n";
				}
			}
		}
	}

?>
