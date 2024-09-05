<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder     = Finder::create()
    ->in(__DIR__)
    ->name('*.php');

$config = new Config();

$rules = [
    '@PER-CS2.0'                      => true,
    'array_indentation'               => true,
    'array_syntax'                    => ['syntax' => 'short'],
    'binary_operator_spaces'          => ['default' => 'align_single_space_minimal'],
    'blank_line_after_namespace'      => true,
    'blank_line_after_opening_tag'    => true,
    'blank_line_before_statement'     => ['statements' => ['return']],
    'braces'                          => ['allow_single_line_closure' => true],
    'cast_spaces'                     => ['space' => 'single'],
    'class_attributes_separation'     => ['elements' => ['method' => 'one']],
    'concat_space'                    => ['spacing' => 'one'],
    'declare_equal_normalize'         => ['space' => 'single'],
    'function_typehint_space'         => true,
    'lowercase_cast'                  => true,
    'no_whitespace_in_blank_line'     => true,
    'single_blank_line_at_eof'        => true,
    'single_quote'                    => true,
    'space_after_semicolon'           => true,
    'trailing_comma_in_multiline'     => ['elements' => ['arrays']],
    'trim_array_spaces'               => true,
    'unary_operator_spaces'           => true,
    'whitespace_after_comma_in_array' => true,
];

return $config
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false);
