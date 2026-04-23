<?php
/**
 * Sniff to discourage use of empty().
 *
 * @package PieStandard
 */

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Warns when empty() is used, encouraging explicit strict checks instead.
 *
 * Function empty() covers a wide range of falsy values and does not communicate intent.
 * Developers should use specific checks based on the expected type of the variable
 * (e.g. isset(), !== '', !== null, count() === 0).
 */
class DiscouragedEmptySniff implements Sniff {

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @return int[]
	 */
	public function register() {
		return array( T_EMPTY );
	}

	/**
	 * Processes the token and flags use of empty().
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$phpcs_file->addWarning(
			'Avoid using empty(). Use strict checks based on what you expect the variable to be (e.g. isset(), !== \'\', !== null, count() === 0).',
			$stack_ptr,
			'Found'
		);
	}
}
