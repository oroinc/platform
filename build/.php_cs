<?php

include_once __DIR__.'/Oro/PhpCsFixer/Fixer/Phpdoc/NoSuperfluousPhpdocTagsFixer.php';

use Oro\PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/../..'])
    ->notPath('doctrine-extensions')
    ->files()
    ->name('*.php')
    ->notName('*.php.twig')
    ->notName('OroKernel.php')
    ->exclude('InstallerBundle/Symfony/Requirements');

// https://github.com/mlocati/php-cs-fixer-configurator
return PhpCsFixer\Config::create()
    ->registerCustomFixers([new NoSuperfluousPhpdocTagsFixer()])
    ->setRules(
        [
            // generic PSRs
            '@PSR1' => true,
            '@PSR2' => true,
            'psr0' => true,
            'psr4' => true,

            // imports
            'ordered_imports' => true,
            'no_unused_imports' => true,
            'no_extra_consecutive_blank_lines' => ['use'],
            'Oro/no_superfluous_phpdoc_tags' => [
                'allow_mixed' => true
            ],
            'no_empty_phpdoc' => true,
            'phpdoc_trim_consecutive_blank_line_separation' => true,
            'phpdoc_trim' => true,
            'no_extra_blank_lines' => [
                'extra',
            ],
            'no_blank_lines_after_class_opening' => true,
            'no_leading_import_slash' => true,
            'no_whitespace_in_blank_line' => true,
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
        ]
    )
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__.DIRECTORY_SEPARATOR.'.php_cs.cache');
