<?php

	require_once 'symbol.class.php';
	require_once 'defines.inc.php';
	require_once 'error.class.php';
	require_once 'ff.inc.php';

	/*
		This class performs syntactic analysis of a given file
	*/
	class SYNTAX {
		//holds an instance of LEXICAL class
		private $lexical = null;
		//holds an instance of CODE class
		private $code = null;
		//holds an instance of SYMBOL class
		private $symbol = null;
		//holds an instance of ERROR class
		private $error = null;
		//holds a chain
		private $chain = '';
		//holds a token
		private $token = '';
		//holds the state of lexical analysis
		private $state = '';
		//holds the state of semantic analysis
		private $semantic = true;
		//holds the debug state
		private $debug = false;
		//holds the position in symbol stack
		private $pos = -1;
		//holds the current context of variable use (declaration, parameter, argument)
		private $context = VAR_DEC;
		//holds the current scope (global or procedure scope)
		private $scope = '';
		//holds the current type of expression
		private $type = '';
		//holds the current called procedure
		private $proc = '';
		//holds the current count of arguments
		private $args = 0;

		/*
			constructor of class SYNTAX
		*/
		public function __construct($lexical, $semantic = false, $code) {
			if (!is_null($lexical)) {
				$this->lexical = $lexical;
				$this->code = $code;
				$this->symbol = new SYMBOL();
				$this->semantic = $semantic;
				$this->error = new ERROR($lexical);
			} else
				die('SYNTAX::__construct() parameter is null!');
		}

		/*
			function that prints the debug text using indentation
		*/
		private function Debug($text) {
			static $indent = 0;
			if ($this->debug) {
				if (substr($text, 1, 1) == '/')
					$indent--;
				echo str_repeat('    ', $indent).$text."\n";
				if (substr($text, 1, 1) != '/')
					$indent++;
			}
				
		}
		
		/*
			function that prints the symbol table
		*/
		private function printST() {
			echo 'Index table:'."\n";
			print_r($this->symbol->index());
			echo "\n\n".'Symbol table:'."\n";
			print_r($this->symbol->table());
		}

		/*
			function that gets a token and check if the grabbed token isn't an error (if so, reports to user)
			also checks if end of file was reached
			returns:	LEXICAL_OK in case of success
						LEXICAL_ERR in case of error
						LEXICAL_EOF in case of end of file
		*/
		private function getToken($error = true) {
			$this->state = $this->lexical->analysis($this->chain, $this->token);
			if ($this->debug)
				echo 'chain: '.$this->chain.' / token: '.$this->token."\n";
			if ($this->state == LEXICAL_ERR) {
				$this->error->show(ERT_ERR, ERR_LEX_INT, $this->lexical->line(), '', $this->chain, $this->token);
				return $this->getToken($error);
			}
			if (($error) && ($this->state == LEXICAL_EOF)) {
				if ($this->lexical->comment())
					$this->error->show(ERT_ERR, ERR_SYN_CBO, $this->lexical->comment(), '', $this->chain, $this->token);
				else
					$this->error->show(ERT_FAT, ERR_GEN_EOF, -1, '', $this->chain, $this->token);
			}
			return $this->state;
		}

		/*
			function that checks if the current chain/token are present into the given table
			returns: true if found; false else.
		*/
		private function checkToken($table = array()) {
			foreach($table as $item) {
				if ((isset($item['chain'])) && ($item['chain'] == $this->chain))
					return true;
				else if ((isset($item['token'])) && ($item['token'] == $this->token))
					return true;
			}
			return false;
		}

		/*
			function that merges two arrays
		*/
		private static function union($a = array(), $b = array()) {
			return array_merge($a, $b);
		}

		/*
			function that transforms a First/Follow table into a string
		*/
		private static function join($a = array()) {
			$ret = '';
			foreach ($a as $item)
				foreach ($item as $key => $value)
					$ret .= $value.', ';
			if ($ret != '')
				$ret = substr($ret, 0, (strlen($ret) - 2));
			return $ret;
		}

		/*
			function that performs the syntactic analysis
			returns:	SYNTAX_OK in case of success
						SYNTAX_WAR in case of some warnings
						SYNTAX_ERR in case of fatal error
		*/
		public function analysis() {
			global $fwPROGRAMA;
			$this->getToken();
			$this->scope = 'global';
			$this->PROGRAMA($fwPROGRAMA);
			if ($this->semantic)
				$this->printST();
			//if after the analysis is done, we reached end of file, the source file is well formed
			if ($this->state == LEXICAL_EOF) {
				//if there were errors, returns SYNTAX_WAR
				if ($this->error->count())
					return SYNTAX_WAR;
				else
					return SYNTAX_OK;
			} else {
				//else, fatal error (program structure malformed)
				$this->error->show(ERT_FAT, ERR_SYN_PRG, -1, '', $this->chain, $this->token);
				return SYNTAX_ERR;
			}
		}

		//<programa> -> program ident ; <corpo> .
		private function PROGRAMA($fw = array()) {
			global $ftCORPO, $fwCORPO;
			$this->Debug('<PROGRAMA>');
			$this->code->insert('INPP');
			if ($this->chain == 'program')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MRW, $this->lexical->line(), 'program', $this->chain, $this->token, SYNTAX::union(array('token' => 'identifier'), $fw));
			if ($this->token == 'identifier') {
				if ($this->semantic)
					$this->symbol->insert($this->chain, $this->token, $this->scope, array('category' => 'program'));
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MID, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union(array('chain' => ';'), $fw));
			if ($this->chain == ';')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MSC, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftCORPO, $fw));
			$this->CORPO(SYNTAX::union($fwCORPO, $fw));
			if ($this->chain == '.')
				$this->getToken(false);
			else
				$this->error->show(ERT_WAR, ERR_SYN_MPE, $this->lexical->line(), '', $this->chain, $this->token);
			if (!$this->error->count())
				$this->code->insert('PARA');
			$this->Debug('</PROGRAMA>');
		}

		//<corpo> -> <dc> begin <comandos> end
		private function CORPO($fw = array()) {
			global $fwDC_V, $fwDC_P, $ftCOMANDOS, $fwCOMANDOS;
			$this->Debug('<CORPO>');
			$this->DC_V(SYNTAX::union($fwDC_V, $fw));
			$this->DC_P(SYNTAX::union($fwDC_P, $fw));
			if ($this->chain == 'begin')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MBW, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftCOMANDOS, $fw));
			$this->COMANDOS(SYNTAX::union($fwCOMANDOS, $fw));
			if ($this->chain == 'end')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MEW, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			$this->Debug('</CORPO>');
		}

		//<dc_v> -> var <variaveis> : <tipo_var> ; <dc_v> | λ
		private function DC_V($fw = array()) {
			global $fwVARIAVEIS, $ftTIPO_VAR, $fwTIPO_VAR, $ftDC;
			$this->Debug('<DC_V>');
			while ($this->chain == 'var') {
				$this->getToken();
				if ($this->semantic) {
					$this->pos = $this->symbol->count($this->scope);
					$this->context = VAR_DEC;
				}
				$this->VARIAVEIS(SYNTAX::union($fwVARIAVEIS, $fw));
				if ($this->chain == ':')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MTD, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftTIPO_VAR, $fw));
				$this->TIPO_VAR(SYNTAX::union($fwTIPO_VAR, $fw));
				if ($this->chain == ';')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MSC, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftDC, $fw));
			}
			$this->Debug('</DC_V>');
		}

		//<tipo_var> -> real | integer
		private function TIPO_VAR($fw = array()) {
			global $ftTIPO_VAR;
			$this->Debug('<TIPO_VAR>');
			if ($this->checkToken($ftTIPO_VAR)) {
				if ($this->semantic) {
					if (($this->context == VAR_DEC) || ($this->context == VAR_PAR))
						$this->symbol->range($this->pos, $this->symbol->count($this->scope), $this->scope, array('type' => $this->chain));
				}
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MVT, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			$this->Debug('</TIPO_VAR>');
		}

		//<variaveis> -> ident <mais_var>
		private function VARIAVEIS($fw = array()) {
			global $ftMAIS_VAR, $fwMAIS_VAR;
			$this->Debug('<VARIAVEIS>');
			if ($this->token == 'identifier') {
				if ($this->semantic)
					switch ($this->context) {
						case VAR_DEC:
							if (!$this->error->count())
								$this->code->insert('ALME 1');
							if (!$this->symbol->insert($this->chain, $this->token, $this->scope, array('category' => 'variable', 'address' => $this->code->getStack())))
								$this->error->show(ERT_WAR, ERR_SEM_ADI, $this->lexical->line(), '', $this->chain, $this->token);
							break;
						case VAR_PAR:
							if (!$this->error->count())
								$this->code->insert('COPVL');
							if (!$this->symbol->insert($this->chain, $this->token, $this->scope, array('category' => 'parameter')))
								$this->error->show(ERT_WAR, ERR_SEM_ADI, $this->lexical->line(), '', $this->chain, $this->token);
							break;
						case VAR_ARG:
							$v = $this->symbol->search($this->chain, $this->scope);
							if ($v == null)
								$v = $this->symbol->search($this->chain);
							if ($v == null)
								$this->error->show(ERT_WAR, ERR_SEM_UDI, $this->lexical->line(), '', $this->chain, $this->token);
							else {
								if ($this->type == '')
									$this->type = $v['properties']['type'];
								else if($this->type != $v['properties']['type'])
									$this->error->show(ERT_WAR, ERR_SEM_IVT, $this->lexical->line(), $this->type, $v['properties']['type'], $this->token);
							}
							break;
					}
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MID, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftMAIS_VAR, $fw));
			$this->MAIS_VAR(SYNTAX::union($fwMAIS_VAR, $fw));
			$this->Debug('</VARIAVEIS>');
		}

		//<mais_var> -> , <variaveis> | λ
		private function MAIS_VAR($fw = array()) {
			global $fwVARIAVEIS;
			$this->Debug('<MAIS_VAR>');
			if ($this->chain == ',') {
				$this->getToken();
				$this->VARIAVEIS(SYNTAX::union($fwVARIAVEIS, $fw));
			}
			$this->Debug('</MAIS_VAR>');
		}

		//<dc_p> -> procedure ident <parametros> ; <corpo_p> <dc_p> | λ
		private function DC_P($fw = array()) {
			global $ftPARAMETROS, $fwPARAMETROS, $ftCORPO_P, $fwCORPO_P;
			$this->Debug('<DC_P>');
			while ($this->chain == 'procedure') {
				$this->getToken();
				if ($this->token == 'identifier') {
					if ($this->semantic) {
						if (!$this->error->count())
							$this->code->insert('DSVI');
						if (!$this->symbol->insert($this->chain, $this->token, $this->scope, array('category' => 'procedure', 'address' => $this->code->getStack())))
							$this->error->show(ERT_WAR, ERR_SEM_ADI, $this->lexical->line(), '', $this->chain, $this->token);
						else
							$this->scope = $this->chain;
					}
					$this->getToken();
				} else
					$this->error->show(ERT_WAR, ERR_SYN_MID, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftPARAMETROS, $fw));
				$this->PARAMETROS(SYNTAX::union($fwPARAMETROS, $fw));
				if ($this->chain == ';')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MSC, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftCORPO_P, $fw));
				$this->CORPO_P(SYNTAX::union($fwCORPO_P, $fw));
				if ($this->semantic) {
					if (!$this->error->count()) {
						$this->code->insert('DESM N');
						$this->code->insert('RTPR');
					}
					$this->scope = 'global';
				}
			}
			$this->Debug('</DC_P>');
		}

		//<parametros> -> ( <lista_par> ) | λ
		private function PARAMETROS($fw = array()) {
			global $fwLISTA_PAR;
			$this->Debug('<PARAMETROS>');
			if ($this->chain == '(') {
				$this->getToken();
				$this->LISTA_PAR(SYNTAX::union($fwLISTA_PAR, $fw));
				if ($this->semantic)
					if ($this->scope != 'global')
						$this->symbol->update($this->scope, 'global', array('parameters' => $this->symbol->count($this->scope)));
				if ($this->chain == ')')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MCB, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			} else
				if ($this->semantic)
					if ($this->scope != 'global')
						$this->symbol->update($this->scope, 'global', array('parameters' => 0));
			$this->Debug('</PARAMETROS>');
		}

		//<lista_par> -> <variaveis> : <tipo_var> <mais_par>
		private function LISTA_PAR($fw = array()) {
			global $fwVARIAVEIS, $ftTIPO_VAR, $fwTIPO_VAR, $fwMAIS_PAR;
			$this->Debug('<LISTA_PAR>');
			if ($this->semantic) {
				$this->pos = $this->symbol->count($this->scope);
				$this->context = VAR_PAR;
			}
			$this->VARIAVEIS(SYNTAX::union($fwVARIAVEIS, $fw));
			if ($this->chain == ':')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MTD, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftTIPO_VAR, $fw));
			$this->TIPO_VAR(SYNTAX::union($fwTIPO_VAR, $fw));
			$this->MAIS_PAR(SYNTAX::union($fwMAIS_PAR, $fw));
			$this->Debug('</LISTA_PAR>');
		}

		//<mais_par> -> ; <lista_par> | λ
		private function MAIS_PAR($fw = array()) {
			$this->Debug('<MAIS_PAR>');
			if ($this->chain == ';') {
				$this->getToken();
				$this->LISTA_PAR($fw);
			}
			$this->Debug('</MAIS_PAR>');
		}

		//<corpo_p> -> <dc_loc> begin <comandos> end ;
		private function CORPO_P($fw = array()) {
			global $fwDC_LOC, $ftCOMANDOS, $fwCOMANDOS;
			$this->Debug('<CORPO_P>');
			$this->DC_LOC(SYNTAX::union($fwDC_LOC, $fw));
			if ($this->chain == 'begin')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MBW, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftCOMANDOS, $fw));
			$this->COMANDOS(SYNTAX::union($fwCOMANDOS, $fw));
			if ($this->chain == 'end')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MEW, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union(array('chain' => ';'), $fw));
			if ($this->chain == ';')
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MSC, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			$this->Debug('</CORPO_P>');
		}

		//<dc_loc> -> <dc_v>
		private function DC_LOC($fw = array()) {
			$this->Debug('<DC_LOC>');
			$this->DC_V($fw);
			$this->Debug('</DC_LOC>');
		}

		//<lista_arg> -> ( <argumentos> ) | λ
		private function LISTA_ARG($fw = array()) {
			global $fwARGUMENTOS;
			$this->Debug('<LISTA_ARG>');
			if ($this->chain == '(') {
				$this->getToken(true, array('chain' => ';'));
				$this->ARGUMENTOS(SYNTAX::union($fwARGUMENTOS, $fw));
				if ($this->semantic) {
					$p = $this->symbol->search($this->proc, $this->scope);
					if ($p == null)
						$p = $this->symbol->search($this->proc);
					if ($p != null) {
						if ($p['properties']['parameters'] > $this->args)
							$this->error->show(ERT_WAR, ERR_SEM_PLA, $this->lexical->line(), $p['properties']['parameters'], $this->args, $this->token);
					}
				}
				if ($this->chain == ')')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MCB, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			} else {
				if ($this->semantic) {
					$v = $this->symbol->search($this->proc);
					if ($v != null)
						if ($v['properties']['parameters'] != 0)
							$this->error->show(ERT_WAR, ERR_SEM_MAR, $this->lexical->line(), '', $this->chain, $this->token);
				}
			}
			$this->Debug('</LISTA_ARG>');
		}

		//<argumentos> -> ident <mais_ident>
		private function ARGUMENTOS($fw = array()) {
			global $ftMAIS_IDENT, $fwMAIS_IDENT;
			$this->Debug('<ARGUMENTOS>');
			if ($this->token == 'identifier') {
				if ($this->semantic) {
					$v = $this->symbol->search($this->chain, $this->scope);
					if ($v == null)
						$v = $this->symbol->search($this->chain);
					if ($v == null)
						$this->error->show(ERT_WAR, ERR_SEM_UDI, $this->lexical->line(), '', $this->chain, $this->token);
					else {
						if (!$this->error->count())
							$this->code->insert('PARAM '.$v['properties']['address']);
						$p = $this->symbol->search($this->proc, $this->scope);
						if ($p == null)
							$p = $this->symbol->search($this->proc);
						if ($p != null) {
							if ($p['properties']['parameters'] == 0)
								$this->error->show(ERT_WAR, ERR_SEM_PNA, $this->lexical->line(), '', $this->chain, $this->token);
							else if ($p['properties']['parameters'] < $this->args)
								$this->error->show(ERT_WAR, ERR_SEM_PMA, $this->lexical->line(), $p['properties']['parameters'], $this->args, $this->token);
							else {
								$a = $this->symbol->indexed($this->args, $this->proc);
								if ($a == null)
									$this->error->show(ERT_WAR, ERR_SEM_PMA, $this->lexical->line(), $this->args, ++$this->args, $this->token);
								else
									if ($a['properties']['type'] != $v['properties']['type'])
										$this->error->show(ERT_WAR, ERR_SEM_IVT, $this->lexical->line(), $a['properties']['type'], $v['properties']['type'], $this->token);
								$this->args++;
							}
						} else
							echo 'p eh null'."\n";
					}
				}
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MID, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftMAIS_IDENT, $fw));
			$this->MAIS_IDENT(SYNTAX::union($fwMAIS_IDENT, $fw));
			$this->Debug('</ARGUMENTOS>');
		}

		//<mais_ident> -> ; <argumentos> | λ
		private function MAIS_IDENT($fw = array()) {
			global $fwARGUMENTOS;
			$this->Debug('<MAIS_IDENT>');
			if ($this->chain == ';') {
				$this->getToken();
				$this->ARGUMENTOS(SYNTAX::union($fwARGUMENTOS, $fw));
			}
			$this->Debug('</MAIS_IDENT>');
		}

		//<pfalsa> -> else <cmd> | λ
		private function PFALSA($fw = array()) {
			global $fwCMD;
			$this->Debug('<PFALSA>');
			if ($this->chain == 'else') {
				$this->getToken();
				$this->CMD(SYNTAX::union($fwCMD, $fw));
			}
			$this->Debug('</PFALSA>');
		}

		//<comandos> -> <cmd> ; <comandos> | λ
		private function COMANDOS($fw = array()) {
			global $ftCMD, $fwCMD, $ftCOMANDOS, $fwCOMANDOS;
			$this->Debug('<COMANDOS>');
			if ($this->checkToken($ftCMD)) {
				$this->CMD(SYNTAX::union($fwCMD, $fw));
				if ($this->chain == ';')
					$this->getToken();
				else
					$this->error->show(ERT_WAR, ERR_SYN_MSC, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftCOMANDOS, $fw));
				$this->COMANDOS(SYNTAX::union($fwCOMANDOS, $fw));
			}
			$this->Debug('</COMANDOS>');			
		}

		//<cmd> -> read ( <variaveis> ) | write ( <variaveis> ) | while <condicao> do <cmd> | 
		//repeat <comandos> until <condicao> | if <condicao> then <cmd> <pfalsa> | ident <cmd_aux> | begin <comandos> end
		private function CMD($fw = array()) {
			global $ftCMD, $fwCMD, $fwCMD_AUX, $ftVARIAVEIS, $fwVARIAVEIS, $ftCONDICAO, $fwCONDICAO, $ftPFALSA, $fwPFALSA, $fwCOMANDOS;
			$this->Debug('<CMD>');
			if ($this->checkToken($ftCMD)) {
				if ($this->token == 'identifier') {
					if ($this->semantic) {
						if (($this->symbol->search($this->chain, $this->scope) == null) && ($this->symbol->search($this->chain) == null))
							$this->error->show(ERT_WAR, ERR_SEM_UDI, $this->lexical->line(), '', $this->chain, $this->token);
						else
							$this->proc = $this->chain;
						$this->type = '';
					}
					$this->getToken();
					$this->CMD_AUX(SYNTAX::union($fwCMD_AUX, $fw));
					if ($this->semantic) {
						$this->proc = '';
						$this->type = '';
					}
				} else {
					$tmp = $this->chain;
					$this->getToken();
					switch ($tmp) {
						case 'read':
						case 'write':
							if ($this->chain == '(')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MOB, $this->lexical->line(), '', $this->chain, $this->token, SYNTAX::union($ftVARIAVEIS, $fw));
							if ($this->semantic) {
								$this->context = VAR_ARG;
								$this->type = '';
							}
							$this->VARIAVEIS(SYNTAX::union($fwVARIAVEIS, $fw));
							if ($this->semantic) {
								if (!$this->error->count()) {
									if ($tmp == 'read')
										$this->code->insert('LEIT');
									else
										$this->code->insert('IMPR');
								}
								$this->type = '';
							}
							if ($this->chain == ')')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MCB, $this->lexical->line(), '', $this->chain, $this->token, $fw);
							break;
						case 'while':
							$this->CONDICAO(SYNTAX::union($fwCONDICAO, $fw));
							if ($this->chain == 'do')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MRW, $this->lexical->line(), 'do', $this->chain, $this->token, SYNTAX::union($ftCMD, $fw));
							$this->CMD(SYNTAX::union($fwCMD, $fw));
							break;
						case 'repeat':
							$this->COMANDOS(SYNTAX::union($fwCMD, $fw));
							if ($this->chain == 'until')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MRW, $this->lexical->line(), 'until', $this->chain, $this->token, SYNTAX::union($ftCONDICAO, $fw));
							$this->CONDICAO(SYNTAX::union($fwCONDICAO, $fw));
							break;
						case 'if':
							$this->CONDICAO(SYNTAX::union($fwCONDICAO, $fw));
							if ($this->chain == 'then')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MRW, $this->lexical->line(), 'then', $this->chain, $this->token, SYNTAX::union($ftCMD, $fw));
							$this->CMD(SYNTAX::union($fwCMD, $fw));
							$this->PFALSA(SYNTAX::union($fwPFALSA, $fw));
							break;
						case 'begin':
							$this->COMANDOS(SYNTAX::union($fwCOMANDOS, $fw));
							if ($this->chain == 'end')
								$this->getToken();
							else
								$this->error->show(ERT_WAR, ERR_SYN_MEW, $this->lexical->line(), '', $this->chain, $this->token, $fw);
							break;
					}
				}
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MRW, $this->lexical->line(), SYNTAX::join($ftCMD), $this->chain, $this->token, $fw);
			$this->Debug('</CMD>');
		}

		//<cmd_aux> -> := <expressao> | <lista_arg>
		private function CMD_AUX($fw = array()) {
			global $fwEXPRESSAO, $fwLISTA_ARG;
			$this->Debug('<CMD_AUX>');
			if (($this->chain == ':=') || ($this->token == 'attribution')) {
				if ($this->semantic) {
					if ($this->proc) {
						$v = $this->symbol->search($this->proc, $this->scope);
						if ($v == null)
							$v = $this->symbol->search($this->proc);
						if ($v == null)
							$this->error->show(ERT_WAR, ERR_SEM_UDI, $this->lexical->line(), '', $this->chain, $this->token);
						else {
							if (($v['properties']['category'] != 'variable') && ($v['properties']['category'] != 'parameter'))
								$this->error->show(ERT_WAR, ERR_SEM_ANV, $this->lexical->line(), '', $this->chain, $this->token);
							else {
								$this->type = $v['properties']['type'];
							}
						}
					}
				}
				$this->getToken();
				$this->EXPRESSAO(SYNTAX::union($fwEXPRESSAO, $fw));
				if ($this->semantic)
					if (!$this->error->count())
						$this->code->insert('ARMZ '.$v['properties']['address']);
			} else {
				if ($this->semantic)
					if (!$this->error->count())
						$this->code->insert('PUSHER $');
				$this->LISTA_ARG(SYNTAX::union($fwLISTA_ARG, $fw));
				if ($this->semantic)
					if (!$this->error->count())
						$this->code->insert('CHPR $');
			}
			if ($this->semantic) {
				$this->proc = '';
				$this->args = 0;
				$this->type = '';
			}
			$this->Debug('</CMD_AUX>');
		}

		//<condicao> -> <expressao> <relacao> <expressao>
		private function CONDICAO($fw = array()) {
			global $fwEXPRESSAO, $fwRELACAO;
			$this->Debug('<CONDICAO>');
			$this->EXPRESSAO(SYNTAX::union($fwEXPRESSAO, $fw));
			$this->RELACAO(SYNTAX::union($fwRELACAO, $fw));
			$this->EXPRESSAO(SYNTAX::union($fwEXPRESSAO, $fw));
			if ($this->semantic)
				$this->type = '';
			$this->Debug('</CONDICAO>');
		}

		//<relacao> -> = | <> | >= | <= | > | <
		private function RELACAO($fw = array()) {
			global $ftRELACAO;
			$this->Debug('<RELACAO>');
			if ($this->checkToken($ftRELACAO))
				$this->getToken();
			else
				$this->error->show(ERT_WAR, ERR_SYN_MRT, $this->lexical->line(), SYNTAX::join($ftRELACAO), $this->chain, $this->token, $fw);
			$this->Debug('</RELACAO>');
		}

		//<expressao> -> <termo> <outros_termos>
		private function EXPRESSAO($fw = array()) {
			global $fwTERMO, $fwOUTROS_TERMOS;
			$this->Debug('<EXPRESSAO>');
			$this->TERMO(SYNTAX::union($fwTERMO, $fw));
			$this->OUTROS_TERMOS(SYNTAX::union($fwOUTROS_TERMOS, $fw));
			$this->Debug('</EXPRESSAO>');
		}

		//<op_un> -> + | - | λ
		private function OP_UN($fw = array()) {
			global $ftOP_UN;
			$this->Debug('<OP_UN>');
			if ($this->checkToken($ftOP_UN)) {
				if ($this->semantic)
					if (!$this->error->count())
						if ($this->chain == '-')
							$this->code->insert('INVE');
				$this->getToken();
			}
			$this->Debug('</OP_UN>');
		}

		//<outros_termos> -> <op_ad> <termo> <outros_termos> | λ
		private function OUTROS_TERMOS($fw = array()) {
			global $ftOP_AD, $fwOP_AD, $fwTERMO, $fwOUTROS_TERMOS;
			$this->Debug('<OUTROS_TERMOS>');
			if ($this->checkToken($ftOP_AD)) {
				$this->OP_AD(SYNTAX::union($fwOP_AD, $fw));
				$this->TERMO(SYNTAX::union($fwTERMO, $fw));
				$this->OUTROS_TERMOS(SYNTAX::union($fwOUTROS_TERMOS, $fw));
			}
			$this->Debug('</OUTROS_TERMOS>');
		}

		//<op_ad> -> + | -
		private function OP_AD($fw = array()) {
			global $ftOP_AD;
			$this->Debug('<OP_AD>');
			if ($this->checkToken($ftOP_AD)) {
				if ($this->semantic)
					if (!$this->error->count()) {
						if ($this->chain == '+')
							$this->code->insert('SOMA');
						else
							$this->code->insert('SUBT');
					}
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYM_MAT, $this->lexical->line(), SYNTAX::join($ftOP_AD), $this->chain, $this->token, $fw);
			$this->Debug('</OP_AD>');
		}

		//<termo> -> <op_un> <fator> <mais_fatores>
		private function TERMO($fw = array()) {
			global $fwOP_UN, $fwFATOR, $fwMAIS_FATORES;
			$this->Debug('<TERMO>');
			$this->OP_UN(SYNTAX::union($fwOP_UN, $fw));
			$this->FATOR(SYNTAX::union($fwFATOR, $fw));
			$this->MAIS_FATORES(SYNTAX::union($fwMAIS_FATORES, $fw));
			$this->Debug('</TERMO>');
		}

		//<mais_fatores> -> <op_mul> <fator> <mais_fatores> | λ
		private function MAIS_FATORES($fw = array()) {
			global $ftOP_MUL, $fwOP_MUL, $fwFATOR, $fwMAIS_FATORES;
			$this->Debug('<MAIS_FATORES>');
			if ($this->checkToken($ftOP_MUL)) {
				$this->OP_MUL(SYNTAX::union($fwOP_MUL, $fw));
				$this->FATOR(SYNTAX::union($fwFATOR, $fw));
				$this->MAIS_FATORES(SYNTAX::union($fwMAIS_FATORES, $fw));
			}
			$this->Debug('</MAIS_FATORES>');
		}

		//<op_mul> -> * | /
		private function OP_MUL($fw = array()) {
			global $ftOP_MUL;
			$this->Debug('<OP_MUL>');
			if ($this->checkToken($ftOP_MUL)) {
				if ($this->semantic)
					if (!$this->error->count()) {
						if ($this->chain == '*')
							$this->code->insert('MULT');
						else
							$this->code->insert('DIVI');
					}
					if ($this->chain == '/')
						if ($this->type != 'integer')
							$this->error->show(ERT_WAR, ERR_SEM_DIV, $this->lexical->line(), '', $this->chain, $this->token);
				$this->getToken();
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MAT, $this->lexical->line(), SYNTAX::join($ftOP_MUL), $this->chain, $this->token, $fw);
			$this->Debug('</OP_MUL>');
		}

		//<fator> -> ident | numero_int | numero_real | ( <expressao> )
		private function FATOR($fw = array()) {
			global $ftFATOR, $fwEXPRESSAO;
			$this->Debug('<FATOR>');
			if ($this->checkToken($ftFATOR)) {
				if ($this->chain == '(') {
					$this->getToken();
					$this->EXPRESSAO(SYNTAX::union($fwEXPRESSAO, $fw));
					if ($this->chain == ')')
						$this->getToken();
					else
						$this->error->show(ERT_WAR, ERR_SYN_MCB, $this->lexical->line(), '', $this->chain, $this->token, $fw);
				} else {
					if ($this->semantic)
						switch ($this->token) {
							case 'identifier':
								$v = $this->symbol->search($this->chain, $this->scope);
								if ($v == null)
									$v = $this->symbol->search($this->chain);
								if ($v == null)
									$this->error->show(ERT_WAR, ERR_SEM_UDI, $this->lexical->line(), '', $this->chain, $this->token);
								else {
									if (!$this->error->count())
										$this->code->insert('CRVL '.$v['properties']['address']);
									if ($this->type == '')
										$this->type = $v['properties']['type'];
									else if (($this->type == 'integer') && ($v['properties']['type'] != 'integer'))
										$this->error->show(ERT_WAR, ERR_SEM_IVT, $this->lexical->line(), $this->type, $v['properties']['type'], $this->token);
								}
								break;
							case 'integer':
							case 'real':
								if (!$this->error->count())
									$this->code->insert('CRCT '.$this->chain);
								if ($this->type == '')
									$this->type = $this->token;
								else
									if (($this->type == 'integer') && ($this->token != 'integer'))
										$this->error->show(ERT_WAR, ERR_SEM_IVT, $this->lexical->line(), $this->type, $this->token, $this->token);
								break;
						}
					$this->getToken();
				}
			} else
				$this->error->show(ERT_WAR, ERR_SYN_MCA, $this->lexical->line(), '', $this->chain, $this->token, $fw);
			$this->Debug('</FATOR>');
		}

	}

?>
