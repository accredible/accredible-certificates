<?php
/**
 * PHP-Scoper configuration for the Accredible Certificates plugin.
 *
 * @package Accredible_Certificates
 */

declare(strict_types=1);

return array(
	// Prefix applied to all vendor code.
	'prefix'                     => 'Accredible\Vendor',

	// Only scope the vendor directory, exclude plugin files.
	'finders'                    => array(
		array(
			'in'      => 'vendor',
			'name'    => '*.php',
			'exclude' => array(
				'vendor/*/tests',
				'vendor/*/Tests',
				'vendor/*/test',
				'vendor/*/Test',
			),
		),
	),

	// Whitelist global functions and classes that shouldn't be prefixed.
	'whitelist-global-functions' => true,
	'whitelist-global-classes'   => true,

	// Exclude files from being scoped (plugin files should not be scoped).
	'exclude-files'              => array(),
);
