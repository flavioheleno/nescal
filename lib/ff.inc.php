<?php

	/*
		Defines First ($ft*) and Follow ($fw*) for non-terminals
	*/

	$ftPROGRAMA = array(
		array('chain' => 'program')
	);
	$fwPROGRAMA = array(
		array('chain' => '$')
	);
	$fwCORPO = array(
		array('chain' => '.')
	);
	$fwDC = array(
		array('chain' => 'begin')
	);
	$ftDC_V = array(
		array('chain' => 'var')
	);
	$ftTIPO_VAR = array(
		array('chain' => 'integer'),
		array('chain' => 'real')
	);
	$fwTIPO_VAR = array(
		array('chain' => ';')
	);
	$ftVARIAVEIS = array(
		array('token' => 'identifier')
	);
	$fwVARIAVEIS = array(
		array('chain' => ':'),
		array('chain' => ')')
	);
	$ftMAIS_VAR = array(
		array('chain' => ',')
	);
	$ftDC_P = array(
		array('chain' => 'procedure')
	);
	$ftPARAMETROS = array(
		array('chain' => '(')
	);
	$fwPARAMETROS = array(
		array('chain' => ';')
	);
	$fwLISTA_PAR = array(
		array('chain' => ')')
	);
	$ftMAIS_PAR = array(
		array('chain' => ';')
	);
	$fwDC_LOC = array(
		array('chain' => 'begin')
	);
	$ftLISTA_ARG = array(
		array('chain' => '(')
	);
	$ftARGUMENTOS = array(
		array('token' => 'identifier')
	);
	$fwARGUMENTOS = array(
		array('chain' => ')')
	);

	$ftMAIS_IDENT = array(
		array('chain' => ';')
	);
	$ftPFALSA = array(
		array('chain' => 'else')
	);
	$fwCOMANDOS = array(
		array('chain' => 'end')
	);
	$ftCMD = array(
		array('chain' => 'read'),
		array('chain' => 'write'),
		array('chain' => 'while'),
		array('chain' => 'repeat'),
		array('chain' => 'if'),
		array('token' => 'identifier'),
		array('chain' => 'begin')
	);
	$fwCMD = array(
		array('chain' => ';')
	);
	$fwCONDICAO = array(
		array('chain' => 'do'),
		array('chain' => 'then')
	);
	$ftRELACAO = array(
		array('chain' => '='),
		array('chain' => '<>'),
		array('chain' => '>='),
		array('chain' => '<='),
		array('chain' => '>'),
		array('chain' => '<')
	);
	$ftOP_UN = array(
		array('chain' => '+'),
		array('chain' => '-')
	);
	$ftOP_AD = array(
		array('chain' => '+'),
		array('chain' => '-')
	);
	$ftOP_MUL = array(
		array('chain' => '*'),
		array('chain' => '/')
	);
	$ftFATOR = array(
		array('token' => 'identifier'),
		array('token' => 'integer'),
		array('token' => 'real'),
		array('chain' => '(')
	);

	$fwMAIS_VAR = $fwVARIAVEIS;
	$fwDC_P = $fwDC;
	$ftLISTA_PAR = $ftVARIAVEIS;
	$fwMAIS_PAR = $fwLISTA_PAR;
	$ftDC_LOC = $ftDC_V;

	$fwMAIS_IDENT = $fwARGUMENTOS;


	$fwOP_UN = $ftFATOR;
	$ftOUTROS_TERMOS = $ftOP_AD;


	$ftMAIS_FATORES = $ftOP_MUL;
	$fwOP_MUL = $ftFATOR;
	$fwFATOR = $ftMAIS_FATORES;

	$ftDC = array_merge(
		$ftDC_V,
		$ftDC_P
	);
	$ftCORPO = array_merge(
		$ftDC,
		array('chain' => 'begin')
	);
	$fwDC_V = array_merge(
		$ftDC_P,
		$fwDC
	);
	$ftCORPO_P = array_merge(
		$ftDC_LOC,
		array('chain' => 'begin')
	);
	$fwCORPO_P = array_merge(
		$ftDC_P,
		$fwDC_P
	);

	$fwLISTA_ARG = $fwCMD;
	$fwPFALSA = $fwCMD;
	$ftCOMANDOS = $ftCMD;
	$fwCMD_AUX = $fwCMD;

	$ftCMD_AUX = array_merge(
		array('chain' => ':='),
		$ftLISTA_ARG
	);
	$fwEXPRESSAO = array_merge(
		$ftRELACAO,
		$fwCONDICAO,
		array('chain' => ')'),
		$fwCMD
	);

	$ftTERMO = array_merge(
		$ftOP_UN,
		$ftFATOR
	);


	$ftEXPRESSAO = $ftTERMO;
	$fwOP_AD = $ftTERMO;
	$ftCONDICAO = $ftEXPRESSAO;
	$fwRELACAO = $ftEXPRESSAO;
	$fwOUTROS_TERMOS = $fwEXPRESSAO;

	$fwTERMO = array_merge(
		$ftOUTROS_TERMOS,
		$fwOUTROS_TERMOS,
		$fwEXPRESSAO
	);

	$fwMAIS_FATORES = $fwTERMO;
?>
