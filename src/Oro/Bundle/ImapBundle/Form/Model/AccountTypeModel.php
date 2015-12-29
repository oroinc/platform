<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class AccountTypeModel
{
    /** @var string|null */
    protected $accountType;

    /** @var UserEmailOrigin */
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
     * @return UserEmailOrigin
     */
    public function getImapGmailConfiguration()
    {
        return $this->imapGmailConfiguration;
    }

    /**
     * @param UserEmailOrigin $value
     */
    public function setImapGmailConfiguration(UserEmailOrigin $value)
    {
        $this->imapGmailConfiguration = $value;
    }
}
