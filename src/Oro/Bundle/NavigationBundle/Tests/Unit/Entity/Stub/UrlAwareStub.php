<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\NavigationBundle\Model\UrlAwareInterface;
use Oro\Bundle\NavigationBundle\Model\UrlAwareTrait;

class UrlAwareStub implements UrlAwareInterface
{
    use UrlAwareTrait;

    /**
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }
}
