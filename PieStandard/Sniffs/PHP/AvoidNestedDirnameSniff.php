<?php
/**
 * Sniff to discourage nesting of dirname() calls.
 *
 * @package PieStandard
 */

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Warns when dirname() is nested inside another dirname() call.
 *
 * Nesting dirname() to traverse directories obscures intent and scatters path
 * logic throughout the codebase. Shared paths should be defined once in a central
 * definitions file at the project root.
 */
class AvoidNestedDirnameSniff implements Sniff {

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @return int[]
	 */
	public function register() {
		return array( T_STRING );
	}

	/**
	 * Processes the token and flags nested dirname() calls.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		if ( 'dirname' !== strtolower( $tokens[ $stack_ptr ]['content'] ) ) {
			return;
		}

		// Skip method/static calls (->dirname() or ::dirname()).
		$prev_token = $phpcs_file->findPrevious( T_WHITESPACE, $stack_ptr - 1, null, true );
		if ( false !== $prev_token ) {
			$prev_code = $tokens[ $prev_token ]['code'];
			if (
				T_OBJECT_OPERATOR === $prev_code
				|| T_NULLSAFE_OBJECT_OPERATOR === $prev_code
				|| T_DOUBLE_COLON === $prev_code
			) {
				return;
			}
		}

		// Confirm this is a function call by checking the next token is an opening parenthesis.
		$open_paren = $phpcs_file->findNext( T_WHITESPACE, $stack_ptr + 1, null, true );
		if ( false === $open_paren || T_OPEN_PARENTHESIS !== $tokens[ $open_paren ]['code'] ) {
			return;
		}

		// Find the first meaningful token inside the parenthesis.
		$first_inner = $phpcs_file->findNext( T_WHITESPACE, $open_paren + 1, null, true );
		if ( false === $first_inner ) {
			return;
		}

		if (
			T_STRING !== $tokens[ $first_inner ]['code']
			|| 'dirname' !== strtolower( $tokens[ $first_inner ]['content'] )
		) {
			return;
		}

		// Confirm the inner token is also a dirname() function call, not just the string "dirname".
		$inner_next = $phpcs_file->findNext( T_WHITESPACE, $first_inner + 1, null, true );
		if ( false === $inner_next || T_OPEN_PARENTHESIS !== $tokens[ $inner_next ]['code'] ) {
			return;
		}

		$phpcs_file->addWarning(
			'Avoid nesting dirname() calls to traverse directories. Use a central definitions file in the project root to store shared paths.',
			$stack_ptr,
			'NestedFound'
		);
	}
}
