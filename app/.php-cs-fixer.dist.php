<?php

$finder = (new PhpCsFixer\Finder())->in([
    __DIR__ . '/src',
    __DIR__ . '/tests',
    __DIR__ . '/migrations'
 ])
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'no_useless_else' => true,
        'phpdoc_order' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'trim_array_spaces' => true,
        //        'cast_spaces' => 'none',
        'phpdoc_align' => [
            'align' => 'left'
        ],
        'return_assignment' => [
            'skip_named_var_tags' => true
        ],
        'declare_strict_types' => [
            'strategy' => 'enforce'
        ],
        'single_quote' => [
            'strings_containing_single_quote_chars' => true
        ]
    ])
    ->setFinder($finder)
;
