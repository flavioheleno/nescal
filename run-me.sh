if [ $# -eq 2 ]; then
	php -f compiler.php $1 $2
else
	echo "usage: $0 type source-file"
	echo "	types:"
	echo "		l: lexical analysis"
	echo "		s: syntactic analysis"
	echo "		e: semantic analysis"
fi
