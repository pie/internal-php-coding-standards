<?php

namespace PieStandard\Sniffs\WordPress;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class EnqueueFilemtimeVersionSniff implements Sniff {

	/**
	 * WordPress enqueue/register functions where the 4th argument is the version.
	 */
	private const TARGET_FUNCTIONS = [
		'wp_enqueue_script',
		'wp_enqueue_style',
		'wp_register_script',
		'wp_register_style',
	];

	public function register() {
		return [ T_STRING ];
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$tokens       = $phpcsFile->getTokens();
		$functionName = strtolower( $tokens[ $stackPtr ]['content'] );

		if ( ! in_array( $functionName, self::TARGET_FUNCTIONS, true ) ) {
			return;
		}

		// Skip method/static calls (->wp_enqueue_script() or ::wp_enqueue_script()).
		$prevToken = $phpcsFile->findPrevious( T_WHITESPACE, $stackPtr - 1, null, true );
		if ( false !== $prevToken ) {
			$prevCode = $tokens[ $prevToken ]['code'];
			if (
				T_OBJECT_OPERATOR === $prevCode
				|| T_NULLSAFE_OBJECT_OPERATOR === $prevCode
				|| T_DOUBLE_COLON === $prevCode
			) {
				return;
			}
		}

		// Find the opening parenthesis immediately after the function name.
		$openParen = $phpcsFile->findNext( T_WHITESPACE, $stackPtr + 1, null, true );
		if ( false === $openParen || T_OPEN_PARENTHESIS !== $tokens[ $openParen ]['code'] ) {
			return;
		}

		$args = $this->getArguments( $phpcsFile, $openParen );

		// Version is the 4th argument (index 3). Fewer args means version defaults to false — skip.
		if ( count( $args ) < 4 ) {
			return;
		}

		$versionArg    = $args[3];
		$versionTokens = [];
		$hasFilemtime  = false;

		for ( $i = $versionArg['start']; $i <= $versionArg['end']; $i++ ) {
			if ( T_WHITESPACE === $tokens[ $i ]['code'] ) {
				continue;
			}

			$versionTokens[] = $tokens[ $i ];

			if ( T_STRING === $tokens[ $i ]['code'] && 'filemtime' === strtolower( $tokens[ $i ]['content'] ) ) {
				$hasFilemtime = true;
			}
		}

		if ( $hasFilemtime || 0 === count( $versionTokens ) ) {
			return;
		}

		// null/false are intentional opt-outs from versioning — skip.
		if ( 1 === count( $versionTokens ) ) {
			$code = $versionTokens[0]['code'];
			if ( T_FALSE === $code || T_NULL === $code ) {
				return;
			}
		}

		$phpcsFile->addWarning(
			'Use filemtime() for asset versioning in %s() to ensure automatic cache-busting (e.g. filemtime( get_template_directory() . \'/build/style.css\' )).',
			$versionArg['start'],
			'MissingFilemtime',
			[ $functionName ]
		);
	}

	/**
	 * Splits a function call's argument list into start/end token index ranges.
	 */
	private function getArguments( File $phpcsFile, $openParen ) {
		$tokens  = $phpcsFile->getTokens();
		$closeParen = $tokens[ $openParen ]['parenthesis_closer'];

		$args            = [];
		$depth           = 0;
		$currentArgStart = $openParen + 1;

		for ( $i = $openParen + 1; $i < $closeParen; $i++ ) {
			$code = $tokens[ $i ]['code'];

			if ( in_array( $code, [ T_OPEN_PARENTHESIS, T_OPEN_SHORT_ARRAY, T_OPEN_SQUARE_BRACKET ], true ) ) {
				$depth++;
			} elseif ( in_array( $code, [ T_CLOSE_PARENTHESIS, T_CLOSE_SHORT_ARRAY, T_CLOSE_SQUARE_BRACKET ], true ) ) {
				$depth--;
			} elseif ( T_COMMA === $code && 0 === $depth ) {
				$args[]          = [ 'start' => $currentArgStart, 'end' => $i - 1 ];
				$currentArgStart = $i + 1;
			}
		}

		$args[] = [ 'start' => $currentArgStart, 'end' => $closeParen - 1 ];

		return $args;
	}
}
