<?php
/**
 * Sniff to enforce filemtime() versioning on WordPress asset enqueue calls.
 *
 * @package PieStandard
 */

namespace PieStandard\Sniffs\WordPress;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Warns when a WordPress enqueue or register function is called with a hardcoded
 * version string instead of filemtime().
 *
 * Using filemtime() as the version argument ensures browsers automatically fetch
 * updated assets after each deployment without requiring manual version changes.
 * Passing null or false is treated as an intentional opt-out and is not flagged.
 */
class EnqueueFilemtimeVersionSniff implements Sniff {

	/**
	 * WordPress enqueue and register functions where the 4th argument is the version.
	 *
	 * @var string[]
	 */
	private const TARGET_FUNCTIONS = array(
		'wp_enqueue_script',
		'wp_enqueue_style',
		'wp_register_script',
		'wp_register_style',
	);

	/**
	 * Returns the token types this sniff is interested in.
	 *
	 * @return int[]
	 */
	public function register() {
		return array( T_STRING );
	}

	/**
	 * Processes the token and flags enqueue calls without filemtime() versioning.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $stack_ptr  The position of the current token in the stack.
	 *
	 * @return void
	 */
	public function process( File $phpcs_file, $stack_ptr ) {
		$tokens        = $phpcs_file->getTokens();
		$function_name = strtolower( $tokens[ $stack_ptr ]['content'] );

		if ( false === in_array( $function_name, self::TARGET_FUNCTIONS, true ) ) {
			return;
		}

		// Skip method/static calls (->wp_enqueue_style() or ::wp_enqueue_style()).
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

		$args = $this->getArguments( $phpcs_file, $open_paren );

		// Version is the 4th argument (index 3). Fewer args means it defaults to false — skip.
		if ( 4 > count( $args ) ) {
			return;
		}

		$version_arg    = $args[3];
		$version_tokens = array();
		$has_filemtime  = false;

		for ( $i = $version_arg['start']; $i <= $version_arg['end']; $i++ ) {
			if ( T_WHITESPACE === $tokens[ $i ]['code'] ) {
				continue;
			}

			$version_tokens[] = $tokens[ $i ];

			if ( T_STRING === $tokens[ $i ]['code'] && 'filemtime' === strtolower( $tokens[ $i ]['content'] ) ) {
				$has_filemtime = true;
			}
		}

		if ( true === $has_filemtime || array() === $version_tokens ) {
			return;
		}

		// null/false are intentional opt-outs from versioning — skip.
		if ( 1 === count( $version_tokens ) ) {
			$code = $version_tokens[0]['code'];
			if ( T_FALSE === $code || T_NULL === $code ) {
				return;
			}
		}

		$phpcs_file->addWarning(
			'Use filemtime() for asset versioning in %s() to ensure automatic cache-busting (e.g. filemtime( get_template_directory() . \'/build/style.css\' )).',
			$version_arg['start'],
			'MissingFilemtime',
			array( $function_name )
		);
	}

	/**
	 * Splits a function call's argument list into an array of start/end token index ranges.
	 *
	 * @param File $phpcs_file The file being scanned.
	 * @param int  $open_paren The stack position of the opening parenthesis.
	 *
	 * @return array<int, array{start: int, end: int}>
	 */
	private function getArguments( File $phpcs_file, $open_paren ) {
		$tokens      = $phpcs_file->getTokens();
		$close_paren = $tokens[ $open_paren ]['parenthesis_closer'];

		$args              = array();
		$depth             = 0;
		$current_arg_start = $open_paren + 1;

		for ( $i = $open_paren + 1; $i < $close_paren; $i++ ) {
			$code = $tokens[ $i ]['code'];

			if ( true === in_array( $code, array( T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET ), true ) ) {
				++$depth;
			} elseif ( true === in_array( $code, array( T_CLOSE_PARENTHESIS, T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET ), true ) ) {
				--$depth;
			} elseif ( T_COMMA === $code && 0 === $depth ) {
				$args[]            = array(
					'start' => $current_arg_start,
					'end'   => $i - 1,
				);
				$current_arg_start = $i + 1;
			}
		}

		$args[] = array(
			'start' => $current_arg_start,
			'end'   => $close_paren - 1,
		);

		return $args;
	}
}
