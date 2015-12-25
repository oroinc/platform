<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

class AccountTypeModel
{
    protected $accountType;

    protected $imapGmailConfiguration;

    public function getAccountType()
    {
        return $this->accountType;
    }

    public function setAccountType($value)
    {
        $this->accountType = $value;
    }

    public function getImapGmailConfiguration()
    {
        return $this->imapGmailConfiguration;
    }

    public function setImapGmailConfiguration($value)
    {
        $this->imapGmailConfiguration = $value;
    }
}
