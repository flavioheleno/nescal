<?php

	/*
		This class is used to handle the file operations
	*/
	class FILE {
		//holds the file status (true for opened, false for closed)
		private $sts = false;
		//holds the file contents
		private $src = '';
		//holds the file size
		private $len = 0;
		//holds the file position
		private $pos = 0;
		//holds the end of file flag
		private $eof = false;

		/*
			function that opens a file
		*/
		public function open($file) {
			if (!$this->sts) {
				$this->src = file_get_contents($file);
				$this->len = strlen($this->src);
				$this->pos = 0;
				$this->eof = false;
				$this->sts = true;
			}
		}

		/*
			function that closes an opened file
		*/
		public function close() {
			if ($this->sts) {
				$this->src = '';
				$this->len = -1;
				$this->pos = -1;
				$this->eof = false;
				$this->sts = false;
			}
		}

		/*
			function that moves the position "pointer" inside the file
		*/
		public function seek($num = 0) {
			if ($this->sts)
				if (($num > 0) && (!$this->eof)) {
					if (($this->pos + $num) > $this->len)
						$num = ($this->len - $this->pos);
					$this->pos += $num;
				} else if ($num < 0) {
					if (($this->pos - $num) < 0)
						$num = $this->pos;
					$this->pos += $num;
				}
		}

		/*
			function that reads a single char from file and checks if we reached end of file
			returns: char
		*/
		public function read() {
			if (($this->sts) && (!$this->eof)) {
				if ($this->pos < $this->len)
					return $this->src[$this->pos++];
				else {
					$this->eof = true;
					return '';
				}
			}
		}
		
		/*
			function that writes a string to a file
			returns: true if success, false else
		*/
		public function write($file, $data) {
			if (!$this->sts)
				return file_put_contents($file, $data);
			else
				return false;
		}

		/*
			property for $len
		*/
		public function len() {
			if ($this->sts)
				return $this->len;
			else
				return -1;
		}

		/*
			property for $pos
		*/
		public function pos() {
			if ($this->sts)
				return $this->pos;
			else
				return -1;
		}

		/*
			property for $eof
		*/
		public function eof() {
			if ($this->sts)
				return $this->eof;
			else
				return false;
		}
	
	}

?>
