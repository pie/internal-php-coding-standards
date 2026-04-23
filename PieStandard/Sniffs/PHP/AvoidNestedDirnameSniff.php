<?php

namespace PieStandard\Sniffs\PHP;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

class AvoidNestedDirnameSniff implements Sniff {

	public function register() {
		return [ T_STRING ];
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( 'dirname' !== strtolower( $tokens[ $stackPtr ]['content'] ) ) {
			return;
		}

		// Skip method/static calls (->dirname() or ::dirname()).
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

		// Confirm this is a function call by finding the opening parenthesis.
		$openParen = $phpcsFile->findNext( T_WHITESPACE, $stackPtr + 1, null, true );
		if ( false === $openParen || T_OPEN_PARENTHESIS !== $tokens[ $openParen ]['code'] ) {
			return;
		}

		// Find the first meaningful token inside the parenthesis.
		$firstInner = $phpcsFile->findNext( T_WHITESPACE, $openParen + 1, null, true );
		if ( false === $firstInner ) {
			return;
		}

		if ( T_STRING !== $tokens[ $firstInner ]['code'] || 'dirname' !== strtolower( $tokens[ $firstInner ]['content'] ) ) {
			return;
		}

		// Confirm the inner dirname is also a function call.
		$innerNext = $phpcsFile->findNext( T_WHITESPACE, $firstInner + 1, null, true );
		if ( false === $innerNext || T_OPEN_PARENTHESIS !== $tokens[ $innerNext ]['code'] ) {
			return;
		}

		$phpcsFile->addWarning(
			'Avoid nesting dirname() calls to traverse directories. Use a central definitions file in the project root to store shared paths.',
			$stackPtr,
			'NestedFound'
		);
	}
}
