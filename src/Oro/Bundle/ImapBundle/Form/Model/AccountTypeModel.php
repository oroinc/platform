<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class AccountTypeModel
{
    const ACCOUNT_TYPE_GMAIL = 'Gmail';
    const ACCOUNT_TYPE_OTHER = 'Other';
    const ACCOUNT_TYPE_NO_SELECT = 'Select Type';

    /** @var string|null */
    protected $accountType;

    /** @var UserEmailOrigin */
    protected $userEmailOrigin;

    /**
     * @return null|string
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * @param string $value
     */
    public function setAccountType($value)
    {
        $this->accountType = $value;
    }

    /**
     * @return UserEmailOrigin
     */
    public function getUserEmailOrigin()
    {
        return $this->userEmailOrigin;
    }

    /**
     * @param UserEmailOrigin $value
     */
    public function setUserEmailOrigin(UserEmailOrigin $value)
    {
        $this->userEmailOrigin = $value;
    }
}
