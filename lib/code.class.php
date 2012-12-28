<?php

	class CODE {
		private $code = null;
		private $stack = -1;
		
		public function __construct() {
			$this->code = array();
			$this->stack = 0;
		}
		
		public function getStack() {
			return $this->stack;
		}
		
		public function output() {
			$count = count($this->code);
			for ($i = 0; $i < $count; $i++)
				echo $i."\t".$this->code[$i]."\n";
		}
		
		public function insert($line) {
			$pos = strpos($line, ' ');
			if ($pos) {
				$opcode = substr($line, 0, $pos++);
				$param = substr($line, $pos);
			} else {
				$opcode = $line;
				$param = '';
			}
			switch ($opcode) {
				case 'CRCT':
				case 'CRVL':
				case 'LEIT':
				case 'PUSHER':
				case 'PARAM':
					$this->stack++;
					break;
				case 'SOMA':
				case 'SUBT':
				case 'MULT':
				case 'DIVI':
				case 'CONJ':
				case 'DISJ':
				case 'CPME':
				case 'CPMA':
				case 'CPIG':
				case 'CDES':
				case 'CPMI':
				case 'CMAI':
				case 'ARMZ':
				case 'IMPR':
				case 'RTPR':
					$this->stack--;
					break;
				case 'INVE':
				case 'NEGA':
				case 'DSVI':
				case 'DSVF':
				case 'PARA':
				case 'COPVL':
				case 'CHPR':
					break;
				case 'ALME':
					$this->stack += intval($param);
					break;
				case 'INPP':
					$this->stack = -1;
					break;
				case 'DESM':
					$this->stack -= intval($param);
					break;
				default:
					$line = 'invalid opcode('.$line.')';
			}
			$this->code[] = $line;
		}
	}

?>
