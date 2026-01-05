<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/../..'])
    ->notPath(['doctrine-extensions', 'upgrade-toolkit'])
    ->files()
    ->name('*.php')
    ->notName('*.php.twig');

// https://github.com/mlocati/php-cs-fixer-configurator
$config = new PhpCsFixer\Config();
$config->setRules([
        // generic PSRs
        '@PSR1' => true,
        '@PSR2' => true,
        'psr_autoloading' => true,

        '@PSR12' => true,

        // imports
        'ordered_imports' => true,
        'no_unused_imports' => true,
        'php_unit_namespaced' => ['target' => '6.0'],
        'php_unit_expectation' => true,

        'general_phpdoc_annotation_remove' => ['annotations' => ['inheritDoc'], 'case_sensitive' => false],
        'no_empty_phpdoc' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.php-cs-fixer.cache');

return $config;
