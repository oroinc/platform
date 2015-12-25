<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

class IMapGmailModel
{
    protected $token;


    public function getToken()
    {
        return $this->token;
    }

    public function setToken($value)
    {
        $this->token = $value;
    }
}
