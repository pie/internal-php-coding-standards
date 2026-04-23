<?php

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class DiscouragedEmptySniff implements Sniff {

	public function register() {
		return [ T_EMPTY ];
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$phpcsFile->addWarning(
			'Avoid using empty(). Use strict checks based on what you expect the variable to be (e.g. isset(), !== \'\', !== null, count() === 0).',
			$stackPtr,
			'Found'
		);
	}
}
