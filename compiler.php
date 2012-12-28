<?php

	require_once 'lib/error.class.php';
	require_once 'lib/syntax.class.php';
	require_once 'lib/code.class.php';
	require_once 'lib/lexical.class.php';
	require_once 'lib/table.class.php';
	require_once 'lib/file.class.php';	

	//prints the banner
	echo "\n";
	echo '  ······································· '."\n";
	echo '·                                         ·'."\n";
	echo '·  NESCAL - PASCAL COMPILER v0.3a         ·'."\n";
	echo '·     created by Flávio Heleno [5890027]  ·'."\n";
	echo '·                                         ·'."\n";
	echo '  ······································· '."\n\n";
	if ($argc < 3) //checks if user passed the source-file as an argument
		echo '[usage] php -f compiler.php analysis-type source-file'."\n";
	else {
		if (!file_exists($argv[2])) //checks if the source-file exists
			echo '[e] file not found ('.$argv[2].')'."\n";
		else {
			//creates the reserved word table
			$reserved = TABLE::reservedGenerate();
			//creates a new instance of FILE class
			$f = new FILE();
			//opens the source-file
			$f->open($argv[2]);
			echo '[i] file size: '.$f->len().' bytes'."\n";
			//creates a new instance of LEXICAL class
			$l = new LEXICAL($f, $reserved);
			switch ($argv[1]) {
				case 'l':
				case 'lexical':
					//only lexical analysis
					echo '[i] performing lexical analysis'."\n";
					$flag = true;
					//prints the table header
					echo 'chain'."\t\t".'token'."\n";
					while ($flag) {
						//grabs a chain/token
						$ret = $l->analysis($chain, $token);
						//if we didn't reach end of file
						if ($ret != LEXICAL_EOF) {
							//prints the chain
							echo $chain;
							if (strlen($chain) > 7)
								echo "\t";
							else
								echo "\t\t";
						}
						//prints then token or end of file warning
						switch ($ret) {
							case LEXICAL_OK:
								echo $token;
								break;
							case LEXICAL_ERR:
								echo 'error';
								break;
							case LEXICAL_EOF:
								$flag = false;
								echo 'end of file';
								break;
						}
						echo "\n";
					}
					break;
				case 's':
				case 'syntactic':
					//both lexical and syntactic analysis
					echo '[i] performing syntactic analysis'."\n";
					//creates a new instance of SYNTAX class
					$s = new SYNTAX($l, false, null);
					//runs syntactic analysis
					switch ($s->analysis()) {
						case SYNTAX_OK:
							echo '[i] file successfully parsed.';
							break;
						case SYNTAX_WAR:
							echo '[i] file has some warnings.';
							break;
						case SYNTAX_ERR:
							echo '[i] file has some fatal error.';
							break;
					}
					break;
				case 'e':
				case 'semantic':
					//lexical, syntactic and semantic analysis
					echo '[i] performing syntactic + semantic analysis'."\n";
					//creates a new instance of CODE class
					$c = new CODE();
					//creates a new instance of SYNTAX class
					$s = new SYNTAX($l, true, $c);
					//runs syntactic analysis
					switch ($s->analysis()) {
						case SYNTAX_OK:
							echo '[i] file successfully parsed.'."\n";
							//writes the generated code to the output file
							$c->output();
							/*
							if ($f->write($argv[2].'.out', $c->output()))
								echo '[i] outputing code to '.$argv[2].'.out';
							else
								echo '[e] output failed.';
							*/
							break;
						case SYNTAX_WAR:
							echo '[i] file has some warnings.';
							break;
						case SYNTAX_ERR:
							echo '[i] file has some fatal error.';
							break;
					}
					break;
			}
			echo "\n";
			//closes the source file
			$f->close();
		}
	}
	echo "\n";
?>
