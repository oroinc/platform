<?php

namespace Oro\Bundle\NavigationBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Provides basic implementation for entities which implement UrlAwareInterface.
 */
trait UrlAwareTrait
{
    /**
     * @var string $url
     */
    #[ORM\Column(name: 'url', type: 'string', length: 8190)]
    protected $url;

    /**
     * @param string $url
     *
     * @return UrlAwareTrait
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}
