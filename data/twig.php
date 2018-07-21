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
        'get_md' =>
        /**
         * @param string $file
         * @param bool $skipCache
         * @return string
         */
            function(string $file, bool $skipCache = false): string {
                return Utility::getMarkdownFile(
                    SOATOK_ROOT . '/data/markdown/' . $file,
                    $skipCache
                );
            }
    ],
    'globals' => [
        'articles' => Soatok::getArticles()
    ]
];

return $return;
