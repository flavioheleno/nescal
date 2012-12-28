<?php
	/*
		This class manages symbol table
	*/
	class SYMBOL {
		//holds the symbol table
		private $table = null;
		//holds the table index
		private $index = null;

		/*
			constructor of class SYMBOL
		*/
		public function __construct() {
			$this->table = array();
			$this->index = array();
		}
		
		/*
			function that returns the array used as symbol table
			returns: local variable table
		*/
		public function table() {
			return $this->table;
		}
		
		/*
			function that returns the array used as table index
			returns: local variable index
		*/
		public function index() {
			return $this->index;
		}
		
		/*
			function that returns the current count of symbol table items
			returns: integer value
		*/
		public function count($scope = 'global') {
			if (isset($this->table[$scope]))
				return count($this->table[$scope]);
			else
				return -1;
		}

		/*
			function used to insert items on symbol table
			returns: true if no collision happened, false else
		*/
		public function insert($chain, $token, $scope = 'global', $properties = array()) {
			if ((!isset($this->index[$scope])) || (!isset($this->index[$scope][$chain]))) {
				$this->table[$scope][] = array(
					'chain' => $chain,
					'token' => $token,
					'properties' => $properties
				);
				$this->index[$scope][$chain] = (count($this->table[$scope]) - 1);
				return true;
			} else
				return false;
		}
		
		/*
			function used to remove items from symbol table
			returns: true if success, false else
		*/
		public function remove($chain, $scope = 'global') {
			if ((isset($this->index[$scope])) && (isset($this->index[$scope][$chain]))) {
				unset($this->table[$scope][$this->index[$scope][$chain]]);
				unset($this->index[$scope][$chain]);
				if ($this->count($scope) == 0)
					$this->decScope();
				return true;
			} else
				return false;
		}
		
		/*
			function used to search items in symbol table by hash table
			returns: array with item properties if found, null else
		*/
		public function search($chain, $scope = 'global') {
			if ((isset($this->index[$scope])) && (isset($this->index[$scope][$chain])))
				return $this->table[$scope][$this->index[$scope][$chain]];
			else
				return null;
		}
		
		/*
			function used to search items in symbol table by its index
			returns: array with item properties if found, null else
		*/
		public function indexed($index, $scope = 'global') {
			if ((isset($this->table[$scope])) && (isset($this->table[$scope][$index])))
				return $this->table[$scope][$index];
			else
				return null;
		}
		
		/*
			function used to update one item in symbol table
		*/
		public function update($chain, $scope = 'global', $properties = array()) {
			if ((isset($this->index[$scope])) && (isset($this->index[$scope][$chain]))) {
				foreach ($properties as $key => $value)
					$this->table[$scope][$this->index[$scope][$chain]]['properties'][$key] = $value;
				return true;
			} else
				return false;
		}
		
		/*
			function used to update multiple items in symbol table
		*/
		public function range($from = -1, $to = -1, $scope = 'global', $properties = array()) {
			if ($from < 0)
				$from = 0;
			if ($to >= $from) {
				if (isset($this->table[$scope])) {
					for ($i = $from; $i < $to; $i++)
						if (isset($this->table[$scope][$i]))
							foreach ($properties as $key => $value)
								$this->table[$scope][$i]['properties'][$key] = $value;
					return true;
				} else
					return false;
			} else
				return false;
		}
	}
?>
