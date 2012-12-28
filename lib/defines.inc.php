<?php

	/*
		ERROR Class defines
	*/
	/* defines the error numbers */
	define('ERR_GEN_UND', 0x00);
	define('ERR_GEN_DEF', 0x01);
	define('ERR_GEN_EOF', 0x02);
	define('ERR_LEX_INT', 0x03);
	define('ERR_SYN_PRG', 0x04);
	define('ERR_SYN_MID', 0x05);
	define('ERR_SYN_MPE', 0x06);
	define('ERR_SYN_MSC', 0x07);
	define('ERR_SYN_MRW', 0x08);
	define('ERR_SYN_MBW', 0x09);
	define('ERR_SYN_MEW', 0x0A);
	define('ERR_SYN_MTD', 0x0B);
	define('ERR_SYN_MVW', 0x0C);
	define('ERR_SYN_MVT', 0x0D);
	define('ERR_SYN_MOB', 0x0E);
	define('ERR_SYN_MCB', 0x0F);
	define('ERR_SYN_MRT', 0x10);
	define('ERR_SYN_MAT', 0x11);
	define('ERR_SYN_MCA', 0x12);
	define('ERR_SYN_CBO', 0x13);
	define('ERR_SEM_ADI', 0x14);
	define('ERR_SEM_UDI', 0x15);
	define('ERR_SEM_IVT', 0x16);
	define('ERR_SEM_PNA', 0x17);
	define('ERR_SEM_PMA', 0x18);
	define('ERR_SEM_PLA', 0x19);
	define('ERR_SEM_MAR', 0x20);
	define('ERR_SEM_ANV', 0x21);
	define('ERR_SEM_DIV', 0x22);

	/* defines the error types */
	define('ERT_WAR', 0);
	define('ERT_ERR', 1);
	define('ERT_FAT', 2);

	/*
		LEXICAL Class defines
	*/
	/* defines the return values for lexical->analysis function */
	define('LEXICAL_OK', 0);
	define('LEXICAL_ERR', 1);
	define('LEXICAL_EOF', 2);

	/* defines the stages, used inside lexical->nalysis function */
	define('STAGE_INIT', 0);
	define('STAGE_DROP', 1);
	define('STAGE_READ', 2);
	define('STAGE_EXEC', 3);

	/* defines the characters used to start and stop a comment block */
	define('COMMENT_START', '{');
	define('COMMENT_STOP', '}');

	/* defines the character type for classify a chain */
	define('TYPE_NOTUSED', 0);
	define('TYPE_REGULAR', 1);
	define('TYPE_NUMERIC', 2);
	define('TYPE_SPECIAL', 3);
	define('TYPE_EOLINE', 4);
	define('TYPE_SEPARATOR', 5);
	define('TYPE_OPERATOR', 6);
	define('TYPE_COMMENT', 7);
	define('TYPE_MIXED', 8);

	/* defines the context of use of a variable */
	define('VAR_DEC', 0);
	define('VAR_PAR', 1);
	define('VAR_ARG', 2);

	/*
		SYNTAX Class defines
	*/
	/* defines the return values for syntax->analysis function */
	define('SYNTAX_OK', 0);
	define('SYNTAX_WAR', 1);
	define('SYNTAX_ERR', 2);

?>
