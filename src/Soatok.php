<?php
declare(strict_types=1);
namespace Soatok\Website;

use Soatok\Website\Engine\Utility;

/**
 * Class Soatok
 * @package Soatok\Website
 */
class Soatok
{
    /**
     * @return array
     * @throws Engine\Exceptions\FileNotFoundException
     * @throws Engine\Exceptions\FileReadException
     * @throws Engine\Exceptions\JSONException
     */
    public static function getArticles(): array
    {
        $articles = Utility::getJsonFile(SOATOK_ROOT . '/data/articles.json');
        uasort(
            $articles,
            function (array $a, array $b): int {
                return (new \DateTime($b['publish-date']))
                        <=>
                   (new \DateTime($a['publish-date']));
            }
        );
        return $articles;
    }
}
