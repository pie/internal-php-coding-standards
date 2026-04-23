<?php
/**
 * Sniff to discourage loose truthiness checks on variables.
 *
 * @package PieStandard
 */

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Warns when an if/elseif condition is a bare variable or its negation.
 *
 * Conditions like if ( $variable ) or if ( ! $variable ) rely on PHP's loose
 * truthiness rules and do not communicate what is actually being checked.
 * Developers should use explicit comparisons based on the expected type
 * (e.g. null !== $var, '' !== $var, false === $var).
 *
 * Only the simplest patterns are flagged — a lone variable, or a lone negated
 * variable — to avoid false positives on complex or compound conditions.
 */
class AvoidLooseTruthinessSniff implements Sniff {

	/**
	 * Token codes that open a new depth level within a condition.
	 *
	 * @var int[]
	 */
	private $openers = array(
		T_OPEN_PARENTHESIS,
		T_OPEN_SQUARE_BRACKET,
		T_OPEN_SHORT_ARRAY,
		T_OPEN_CURLY_BRACKET,
	);

	/**
	 * Token codes that close a depth level within a condition.
	 *
	 * @var int[]
	 */
	private $closers = array(
		T_CLOSE_PARENTHESIS,
		T_CLOSE_SQUARE_BRACKET,
		T_CLOSE_SHORT_ARRAY,
		T_CLOSE_CURLY_BRACKET,
	);

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @return int[]
	 */
	public function register() {
		return array( T_IF, T_ELSEIF );
	}

	/**
	 * Processes the token and flags bare variable truthiness checks.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens = $phpcs_file->getTokens();

		if ( false === isset( $tokens[ $stack_ptr ]['parenthesis_opener'] ) ) {
			return;
		}

		$open_paren  = $tokens[ $stack_ptr ]['parenthesis_opener'];
		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];

		// Collect non-whitespace token codes from inside the condition.
		$cond_tokens = array();
		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			if ( T_WHITESPACE !== $tokens[ $i ]['code'] ) {
				$cond_tokens[] = $tokens[ $i ]['code'];
			}
		}

		if ( array() === $cond_tokens ) {
			return;
		}

		// Build a list of depth-0 token codes to identify the shape of the condition.
		// Openers are recorded at the current depth before incrementing;
		// closers are recorded at the new depth after decrementing.
		$depth         = 0;
		$depth_0_codes = array();

		foreach ( $cond_tokens as $code ) {
			if ( true === in_array( $code, $this->openers, true ) ) {
				$depth_0_codes[] = $code;
				++$depth;
			} elseif ( true === in_array( $code, $this->closers, true ) ) {
				--$depth;
				$depth_0_codes[] = $code;
			} elseif ( 0 === $depth ) {
				$depth_0_codes[] = $code;
			}
		}

		if ( array( T_VARIABLE ) === $depth_0_codes ) {
			$phpcs_file->addWarning(
				'Avoid using if ( $variable ). Use a specific check based on what the variable should be (e.g. null !== $var, \'\' !== $var, false !== $var).',
				$stack_ptr,
				'BareVariable'
			);
			return;
		}

		if ( array( T_BOOLEAN_NOT, T_VARIABLE ) === $depth_0_codes ) {
			$phpcs_file->addWarning(
				'Avoid using if ( ! $variable ). Use a specific check based on what the variable should be (e.g. null === $var, \'\' === $var, false === $var).',
				$stack_ptr,
				'NegatedVariable'
			);
		}
	}
}
