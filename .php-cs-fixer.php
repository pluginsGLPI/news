<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->name('*.php');

$config = new Config();

$rules = [
    '@PER-CS2.0'                  => true,
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],
];

return $config
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false);
