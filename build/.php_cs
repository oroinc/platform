<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/../..'])
    ->files()
    ->name('*.php')
    ->notName('*.php.twig')
;

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'psr4' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
;
