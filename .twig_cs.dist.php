<?php

declare(strict_types=1);

use FriendsOfTwig\Twigcs;

$finder = Twigcs\Finder\TemplateFinder::create()
    ->in(__DIR__ . '/templates')->in(__DIR__ . '/templates/components')
    ->name('*.html.twig')
    ->ignoreVCSIgnored(true);

return Twigcs\Config\Config::create()
    ->setFinder($finder)
    ->setRuleSet(\Glpi\Tools\GlpiTwigRuleset::class)
;
