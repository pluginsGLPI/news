<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

/**
 * Read excluded paths from .gitignore
 *
 * @param string $dir
 *
 * @return string[]
 */
function getGitignorePaths(string $dir): array
{
    $gitignoreFile = $dir . '/.gitignore';
    $paths         = [];

    if (!file_exists($gitignoreFile)) {
        return $paths;
    }

    $lines = file($gitignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!empty($lines)) {
        foreach ($lines as $line) {
            // Ignore comments and empty lines
            if (strpos($line, '#') === 0 || trim($line) === '') {
                continue;
            }
            // Add relative paths
            $paths[] = trim($line);
        }
    }

    return $paths;
}

$projectDir = __DIR__;
$finder     = Finder::create()
    ->in($projectDir)
    ->name('*.php');

// Exclude paths from .gitignore
$gitignorePaths = getGitignorePaths($projectDir);
foreach ($gitignorePaths as $path) {
    $finder->notPath($path);
}

$config = new Config();

$mandatoryRules = [
    '@PSR12'                          => true,
    '@PER-CS2.0'                      => true,
];

$optionalRules = [
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

$isFix = in_array('fix', $_SERVER['argv'], true);
$rules = $isFix ? array_merge($mandatoryRules, $optionalRules) : $mandatoryRules;

return $config
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false);
