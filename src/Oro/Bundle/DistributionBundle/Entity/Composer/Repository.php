<?php

namespace Oro\Bundle\DistributionBundle\Entity\Composer;


class Repository {

    protected $type = 'vcs';

    protected $url;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }
}