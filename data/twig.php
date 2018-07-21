<?php
declare(strict_types=1);
namespace Soatok\Website;

/* This file must return an array. */

/**
 * @var array<string, array<string, callable>> $return
 */
use Soatok\Website\Engine\GlobalConfig;
use Soatok\Website\Engine\Utility;

$return = [
    'filters' => [
        'markdown' => function(string $input): string {
            $config = (GlobalConfig::instance());
            return $config->getMarkdownRenderer()->convertToHtml(
                $config->getHtmlPurifier()->purify($input)
            );
        },
        'purify' => function(string $input): string {
            return (GlobalConfig::instance())
                ->getHtmlPurifier()
                ->purify($input);
        }
    ],
    'functions' => [
        'get_md' => function(string $file): string {
            return Utility::getMarkdownFile(
                SOATOK_ROOT . '/data/markdown/' . $file
            );
        }
    ],
    'globals' => [
        'articles' => Soatok::getArticles()
    ]
];

return $return;
