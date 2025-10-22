<?php

$header = <<<'HEADER'
This file is part of the Liip/FunctionalTestBundle

(c) Lukas Kahwe Smith <smith@pooteeweet.org>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
HEADER;

$finder = PhpCsFixer\Finder::create()
    ->in(dirname(__DIR__))
    ->notPath('/cache/')
;

$config = new PhpCsFixer\Config();
return $config
    ->setParallelConfig(\PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'header_comment' => [
            'header' => $header,
        ],
        'no_extra_blank_lines' => true,
        'no_php4_constructor' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => true,
        'phpdoc_order' => true,
        '@PHP8x0Migration' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'php_unit_strict' => true,
    ])
    ->setUsingCache(true)
;
