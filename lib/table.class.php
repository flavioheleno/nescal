<?php

	class TABLE {
		/*
			function used to create reserved word table
			returns: a vector with all reserved words
		*/
		public static function reservedGenerate() {
			return array(
				'begin',
				'do',
				'else',
				'end',
				'if',
				'integer',
				'procedure',		
				'program',
				'read',
				'real',
				'repeat',
				'then',	
				'until',	
				'var',
				'while',
				'write'
			);
		}

		/*
			procedure used to sort our table using quick sort
		*/
		public static function sort(&$table = array()) {
			sort($table, SORT_STRING);
		}

		/*
			function used to check if a needle item is in a table contents
			returns: true if found, false else
		*/
		public static function check($table = array(), $needle) {
			return (TABLE::search($table, $needle) != -1);
		}

		/*
			function used to search if a needle item is in a table contents (binary search)
			returns: index if found, -1 else
		*/
		public static function search($table = array(), $needle) { 
			$left = 0;
			$right = (count($table) - 1);
			while($left <= $right) {
				$pivot = floor(($right + $left) / 2);
				$cmp = strcasecmp($table[$pivot], $needle);
				if ($cmp < 0)
					$left = $pivot + 1;
				else if ($cmp > 0)
					$right = $pivot - 1;
				else
					return $pivot;
			}
			return -1;
		}
	}
?>
