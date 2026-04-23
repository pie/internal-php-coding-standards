<?php
/**
 * PieStandard examples.
 *
 * This file demonstrates each custom sniff in action.
 * The "triggers" block shows code that will produce a warning.
 * The "fixed" block shows the correct approach.
 *
 * @package PieStandard
 */

// -----------------------------------------------------------------------------
// PieStandard.PHP.DiscouragedEmpty
// -----------------------------------------------------------------------------

// Triggers warning — avoid empty(), it masks what is actually being checked.
if ( empty( $value ) ) {
	$empty = true;
}

// Fixed — use a specific check based on what the variable should be.
if ( isset( $value ) && '' !== $value ) {
	$empty = true;
}

// -----------------------------------------------------------------------------
// PieStandard.PHP.AvoidNestedDirname
// -----------------------------------------------------------------------------

// Triggers warning — nested dirname() calls to traverse directories.
$path_test = dirname( dirname( __DIR__ ) );

// Fixed — use the $levels argument, or better, a shared path constant from a central definitions file.
$path_test = dirname( __DIR__, 2 );

// -----------------------------------------------------------------------------
// PieStandard.PHP.AvoidLooseTruthiness
// -----------------------------------------------------------------------------

// Triggers warning — bare variable truthiness check.
if ( $variable ) {
	$test = false;
}

// Triggers warning — negated variable truthiness check.
if ( ! $variable ) {
	$test = true;
}

// Fixed — use a specific comparison based on what the variable should be.
if ( true === $variable ) {
	$test = false;
}

if ( false === $variable ) {
	$test = true;
}

// -----------------------------------------------------------------------------
// PieStandard.WordPress.EnqueueFilemtimeVersion
// -----------------------------------------------------------------------------

// Triggers warning — hardcoded version string does not bust the browser cache automatically.
wp_enqueue_style( 'my-style', get_template_directory_uri() . '/style.css', array(), '1.0.0' );

// Fixed — filemtime() uses the file's modification time as the version, ensuring automatic cache-busting.
wp_enqueue_style( 'my-style', get_template_directory_uri() . '/style.css', array(), filemtime( get_template_directory() . '/style.css' ) );
