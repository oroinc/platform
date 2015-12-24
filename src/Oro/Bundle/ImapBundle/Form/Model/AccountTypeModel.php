<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

class AccountTypeModel
{
    protected $accountType;

    public function getAccountType()
    {
        return $this->accountType;
    }

    public function setAccountType($value)
    {
        $this->accountType = $value;
    }
}
