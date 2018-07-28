<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Traits;

use Soatok\Website\Engine\Contract\RequestHandlerInterface;

/**
 * Trait RequestHandlerTrait
 * @package Soatok\Website\Engine\Traits
 */
trait RequestHandlerTrait
{
    /** @var array $postData */
    protected $postData = [];

    /**
     * @return array<string, mixed>
     */
    public function getPostData(): array
    {
        return $this->postData;
    }

    /**
     * @param array $post
     * @return void
     */
    public function setPostData(array $post)
    {
        $this->postData = $post;
    }
}
