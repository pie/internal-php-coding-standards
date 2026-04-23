<?php

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class AvoidLooseTruthinessSniff implements Sniff {

	public function register() {
		return [ T_IF, T_ELSEIF ];
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( ! isset( $tokens[ $stackPtr ]['parenthesis_opener'] ) ) {
			return;
		}

		$openParen  = $tokens[ $stackPtr ]['parenthesis_opener'];
		$closeParen = $tokens[ $openParen ]['parenthesis_closer'];

		// Collect non-whitespace tokens inside the condition.
		$condTokens = [];
		for ( $i = $openParen + 1; $i < $closeParen; $i++ ) {
			if ( T_WHITESPACE !== $tokens[ $i ]['code'] ) {
				$condTokens[] = $tokens[ $i ]['code'];
			}
		}

		if ( [] === $condTokens ) {
			return;
		}

		// Collect depth-0 token codes to identify the shape of the condition.
		// Openers increase depth after being recorded; closers decrease depth before being recorded.
		$depth       = 0;
		$depth0Codes = [];

		$openers = [ T_OPEN_PARENTHESIS, T_OPEN_SQUARE_BRACKET, T_OPEN_SHORT_ARRAY, T_OPEN_CURLY_BRACKET ];
		$closers = [ T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET, T_CLOSE_SHORT_ARRAY, T_CLOSE_CURLY_BRACKET ];

		foreach ( $condTokens as $code ) {
			if ( in_array( $code, $openers, true ) ) {
				$depth0Codes[] = $code;
				$depth++;
			} elseif ( in_array( $code, $closers, true ) ) {
				$depth--;
				$depth0Codes[] = $code;
			} elseif ( 0 === $depth ) {
				$depth0Codes[] = $code;
			}
		}

		if ( [ T_VARIABLE ] === $depth0Codes ) {
			$phpcsFile->addWarning(
				'Avoid using if ( $variable ). Use a specific check based on what the variable should be (e.g. null !== $var, \'\' !== $var, false !== $var).',
				$stackPtr,
				'BareVariable'
			);
			return;
		}

		if ( [ T_BOOLEAN_NOT, T_VARIABLE ] === $depth0Codes ) {
			$phpcsFile->addWarning(
				'Avoid using if ( ! $variable ). Use a specific check based on what the variable should be (e.g. null === $var, \'\' === $var, false === $var).',
				$stackPtr,
				'NegatedVariable'
			);
		}
	}
}
