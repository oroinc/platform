<?php

namespace Oro\Bundle\NavigationBundle\Model;

interface UrlAwareInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @param string $url
     *
     * @return UrlAwareInterface
     */
    public function setUrl($url);
}
