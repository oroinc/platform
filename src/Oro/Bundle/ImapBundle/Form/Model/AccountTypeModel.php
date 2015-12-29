<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

use Oro\Bundle\ImapBundle\Entity\OauthEmailOrigin;

class AccountTypeModel
{
    /** @var string|null */
    protected $accountType;

    /** @var OauthEmailOrigin */
    protected $imapGmailConfiguration;

    /**
     * @return null|string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param $value
     */
    public function setAccountType($value)
    {
        $this->accountType = $value;
    }

    /**
     * @return OauthEmailOrigin
     */
    public function getImapGmailConfiguration()
    {
        return $this->imapGmailConfiguration;
    }

    /**
     * @param OauthEmailOrigin $value
     */
    public function setImapGmailConfiguration(OauthEmailOrigin $value)
    {
        $this->imapGmailConfiguration = $value;
    }
}
