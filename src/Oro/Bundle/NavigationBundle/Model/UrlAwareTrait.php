<?php

namespace Oro\Bundle\NavigationBundle\Model;

trait UrlAwareTrait
{
    /**
     * @var string $url
     *
     * @ORM\Column(name="url", type="string", length=1023)
     */
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
