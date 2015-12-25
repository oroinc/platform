<?php

namespace Oro\Bundle\ImapBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;

class OauthEmailOrigin extends EmailOrigin
{
    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="string", length=255, nullable=true)
     */
    protected $accessToken;

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param $accessToken
     *
     * @return OauthEmailOrigin
     */
    public function setServer($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
