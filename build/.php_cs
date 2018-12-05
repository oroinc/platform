#!/usr/bin/env php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/../..'])
    ->notPath('doctrine-extensions')
    ->files()
    ->name('*.php')
    ->notName('*.php.twig')
    ->notName('OroKernel.php');

// https://github.com/mlocati/php-cs-fixer-configurator
return PhpCsFixer\Config::create()
    ->setRules(
        [
            // generic PSRs
            '@PSR1' => true,
            '@PSR2' => true,
            'psr0' => true,
            'psr4' => true,

            // imports
            'ordered_imports' => true,
            'no_extra_consecutive_blank_lines' => ['use'],
            'php_unit_namespaced' => ['target' => '6.0'],
            'php_unit_expectation' => true,

            // Symfony, but exclude Oro cases
//            '@Symfony' => true,
//            '@Symfony:risky' => true,
//            'yoda_style' => false,

//            '@DoctrineAnnotation' => true,
//            'align_multiline_comment' => ['comment_type' => 'phpdocs_only'],
//            'array_syntax' => ['syntax' => 'short'],
//            'binary_operator_spaces' => false,
//            'blank_line_before_return' => true,
//            'declare_strict_types' => false,
//            'increment_style' => false,
//            'list_syntax' => ['syntax' => 'short'],
//            'multiline_comment_opening_closing' => false,
//            'space_after_semicolon' => true,
//            'no_empty_statement' => true,
//            'phpdoc_add_missing_param_annotation' => true,
//            'phpdoc_align' => ['tags' => ['param']],
//            'phpdoc_order' => true,
//            'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
        ]
    )
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__.DIRECTORY_SEPARATOR.'.php_cs.cache');
