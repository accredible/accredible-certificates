<?php
/**
 * PHP-Scoper configuration for the Accredible Certificates plugin.
 *
 * @package Accredible_Certificates
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

return array(
	// Prefix applied to all vendor code.
	'prefix'                     => 'Accredible\Vendor',

	// Only scope the vendor directory, exclude plugin files.
	'finders'                    => array(
		Finder::create()
			->files()
			->in('vendor')
			->name('*.php')
			->exclude(array(
				'tests',
				'Tests',
				'test',
				'Test',
			)),
	),

	// Exclude files from being scoped (plugin files should not be scoped).
	'exclude-files'              => array(),
);
