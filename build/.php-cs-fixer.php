<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/../..'])
    ->notPath('doctrine-extensions')
    ->files()
    ->name('*.php')
    ->notName('*.php.twig')
    ->notName('ClientController.php') // braces rule, multiline function declaration ending with "): callable {"
    ->notName('OroKernel.php');

// https://github.com/mlocati/php-cs-fixer-configurator
$config = new PhpCsFixer\Config();
$config->setRules([
        // generic PSRs
        '@PSR1' => true,
        '@PSR2' => true,
        'psr_autoloading' => true,

        // imports
        'ordered_imports' => true,
        'no_unused_imports' => true,
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

        // Otherwise anonymous classes cannot be used with any meaningful constructor arguments.
        // It is temporary for now, until a decision is made by PSR-12 editors (see
        // https://github.com/php-fig/fig-standards/pull/1206#issuecomment-628873709 ) and
        // and a PR to php-cs-fixer will be proposed based on that decision to address it one way or the other.
        'class_definition' => false,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.php-cs-fixer.cache');

return $config;
