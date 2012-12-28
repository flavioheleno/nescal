<?php

	require_once 'defines.inc.php';
	require_once 'table.class.php';

	/*
		This class performs lexical analysis of a given file
	*/
	class LEXICAL {
		//holds an instance of FILE class
		private $file = null;
		//holds reserved word table
		private $reserved = '';
		//holds line counter
		private $line = 0;
		//holds comment start position
		private $comment = 0;

		/*
			constructor of class LEXICAL
		*/
		public function __construct($file, $reserved) {
			if (!is_null($file))
				$this->file = $file;
			else
				die('LEXICAL::__construct() parameter is null!');
			$this->reserved = $reserved;
		}

		/*
			function used to match a text by a pattern, using regular expressions
			returns: 1 for successful match or 0 for non-matching
		*/
		private static function regexMatch($pattern, $text) {
			return preg_match($pattern, $text);
		}
		
		/*
			property for $line
		*/
		public function line() {
			return $this->line;
		}
		
		/*
			property for $comment
		*/
		public function comment() {
			return $this->comment;
		}

		/*
			function used to retrieve a chain and it's token identifier
			returns:	LEXICAL_OK in case of success
						LEXICAL_ERR in case of error
						LEXICAL_EOF in case of end of file
		*/
		public function analysis(&$chain, &$token) {
			//initialize chain variable
			$chain = '';
			//initialize token variable
			$token = '';
			//holds nested comment count
			$comment = 0;
			//holds the current char type
			$type = TYPE_NOTUSED;
			//holds the analysis stage
			$stage = STAGE_INIT;
			//work buffer (holds the chain)
			$buffer = '';
			//loop control flag
			$flag = true;
			while ($flag) {
				//if there is some char to be read, it'll be read, else, LEXICAL_EOF is returned
				if ($this->file->eof())
					return LEXICAL_EOF;
				$c = $this->file->read();
				//controls the line number counter
				if (($buffer == '') && ($c == "\n"))
					$this->line++;
				switch ($stage) {
					/*
						Initial Stage
						here, the first char is classified and buffered
					*/
					case STAGE_INIT:
						if (LEXICAL::regexMatch('/[a-z]/i', $c)) { //regular chars
							$type = TYPE_REGULAR;
							$buffer = $c;
							$stage = STAGE_READ;
						} else if (LEXICAL::regexMatch('/[0-9]/', $c)) { //numbers
							$type = TYPE_NUMERIC;
							$buffer = $c;
							$stage = STAGE_READ;
						} else if (LEXICAL::regexMatch('/[<>=:]/', $c)) { //special chars
							$type = TYPE_SPECIAL;
							$buffer = $c;
							$stage = STAGE_READ;
						} else if (LEXICAL::regexMatch('/[;\.]/', $c)) { //end of line chars
							$type = TYPE_EOLINE;
							$buffer = $c;
							$stage = STAGE_EXEC;
						} else if (LEXICAL::regexMatch('/[\(\),]/', $c)) { //separator chars
							$type = TYPE_SEPARATOR;
							$buffer = $c;
							$stage = STAGE_READ;
						} else if (LEXICAL::regexMatch('/[+\-*\/]/', $c)) { //operators
							$type = TYPE_OPERATOR;
							$buffer = $c;
							$stage = STAGE_READ;
						} else if ($c == COMMENT_START) { //start of comment block
							$type = TYPE_COMMENT;
							$comment = 1;
							$stage = STAGE_DROP;
							$this->comment = $this->line;
						} else if (!LEXICAL::regexMatch('/[ \t\r\n]/', $c)) { //structure chars
							$buffer = $c;
							$stage = STAGE_EXEC;
						}
						break;
					/*
						Drop Stage
						here, all the chars inside a comment block are dropped out
					*/
					case STAGE_DROP:
						//if we found another comment start, increase nested comment counter
						if ($c == COMMENT_START)
							$comment++;
						//if we found a comment stop, decrease nested comment counter and if counter = 0, changes stage to STAGE_READ
						if ($c == COMMENT_STOP) {
							$comment--;
							if (!$comment) {
								$type = TYPE_NOTUSED;
								$stage = STAGE_INIT;
								$this->comment = 0;
							}
						}
						break;
					/*
						Read Stage
						here, we can identify if the current char is of the same type we were reading, and if so we bufferize it,
						else we change our stage to STAGE_EXEC and return 1 step on file reading
					*/
					case STAGE_READ:
						if (LEXICAL::regexMatch('/[a-z]/i', $c)) { //regular chars
							if (($type == TYPE_REGULAR) || ($type == TYPE_MIXED))
								$buffer .= $c;
							else if ($type == TYPE_NUMERIC) {
								$buffer .= $c;
								$type = TYPE_MIXED;
							} else {
								$this->file->seek(-1);
								$stage = STAGE_EXEC;
							}
						} else if (LEXICAL::regexMatch('/[0-9]/', $c)) { //numbers (can be regular too => identifier)
							if (($type == TYPE_REGULAR) || ($type == TYPE_NUMERIC) || ($type == TYPE_MIXED))
								$buffer .= $c;
							else {
								$this->file->seek(-1);
								$stage = STAGE_EXEC;
							}
						} else if ($c == '.') { //period (can be numeric too => real number)
							if (($type == TYPE_NUMERIC) || ($type == TYPE_MIXED))
								$buffer .= $c;
							else {
								$this->file->seek(-1);
								$stage = STAGE_EXEC;
							}
						} else if (LEXICAL::regexMatch('/[<>=:]/', $c)) { //special chars
							if ($type == TYPE_SPECIAL) {
								if ((($c == ':') && ($buffer != ':')) || ($c != ':'))
									$buffer .= $c;
								else {
									$this->file->seek(-1);
									$stage = STAGE_EXEC;
								}
							} else {
								$this->file->seek(-1);
								$stage = STAGE_EXEC;
							}
						} else if (LEXICAL::regexMatch('/[;]/', $c)) { //end of line chars
							$this->file->seek(-1);
							$stage = STAGE_EXEC;
						} else if (LEXICAL::regexMatch('/[\(\),]/', $c)) { //separator chars
							$this->file->seek(-1);
							$stage = STAGE_EXEC;
						} else if (LEXICAL::regexMatch('/[+\-*\/]/', $c)) { //operators
							if ($type == TYPE_OPERATOR)
								$buffer .= $c;
							else {
								$this->file->seek(-1);
								$stage = STAGE_EXEC;
							}
						} else if ($c == COMMENT_START) {
							$this->file->seek(-1);
							$stage = STAGE_EXEC;
						} else if (LEXICAL::regexMatch('/[ \r\n\t]/', $c)) { //if we got some of the struct chars, go to STAGE_EXEC
							$this->file->seek(-1);
							$stage = STAGE_EXEC;
						} else { //if we got some char, that we weren't expecting, if we can, bufferize it
							if (($type == TYPE_REGULAR) || ($type == TYPE_NUMERIC)) {
								$buffer .= $c;
								$type = TYPE_MIXED;
							} else if ($type == TYPE_MIXED)
								$buffer .= $c;
						}
						break;
					/*
						Execute Stage
						here, we classify our buffer into some of our tokens
					*/
					case STAGE_EXEC:
						$this->file->seek(-1);
						if ($buffer != '') { //if our buffer isn't empty (buffer will be empty if the char(s) read(ed), is(are) not used)
							$chain = $buffer;
							if (TABLE::check($this->reserved, $buffer)) //our buffer is inside the reserved word table
								$token = 'reserved';
							else if (LEXICAL::regexMatch('/^[0-9]+$/i', $buffer)) //our buffer is an integer number
								$token = 'integer';
							else if (LEXICAL::regexMatch('/^[0-9]+\.[0-9]+$/', $buffer)) //our buffer is a real number
								$token = 'real';
							else if (LEXICAL::regexMatch('/^[a-z][a-z0-9]*$/i', $buffer)) //our buffer is an identifier
								$token = 'identifier';
							else if (LEXICAL::regexMatch('/^[<>=]+$/', $buffer)) //our buffer is a relational operand
								$token = 'relational';
							else if (LEXICAL::regexMatch('/^[:;\.]$/', $buffer)) //our buffer is a marker
								$token = 'marker';
							else if (LEXICAL::regexMatch('/^[\(\),]$/', $buffer)) //our buffer is a separator
								$token = 'separator';
							else if (LEXICAL::regexMatch('/^[+\-*\/]$/', $buffer)) //our buffer is an operator
								$token = 'operator';
							else if ($buffer == ':=') //our buffer is the attribution operand
								$token = 'attribution';
							else //our buffer is an invalid chain
								$token = '';
							//echo 'read: '.$chain.' - '.$token."\n";
							if ($token != '')
								return LEXICAL_OK;
							else
								return LEXICAL_ERR;
						} else //as our buffer is empty, let's get back to initial stage
							$stage = STAGE_INIT;
						break;
				}
			}
		}
	}
?>
