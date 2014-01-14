<?php

namespace Oro\Bundle\DistributionBundle\Entity\Composer;

use Symfony\Component\Validator\Constraints as Assert;

class Repository
{

    /**
     * @Assert\Choice(choices = {"composer", "vcs", "pear"}, message = "Choose a valid type.")
     * @var string
     */
    protected $type = 'composer';

    /**
     * @Assert\NotBlank()
     */
    protected $url;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
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
