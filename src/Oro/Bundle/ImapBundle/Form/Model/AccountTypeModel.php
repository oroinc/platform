<?php

namespace Oro\Bundle\ImapBundle\Form\Model;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Value object for Account type form type - contains
 * user email origin entity and account type name
 */
class AccountTypeModel
{
    public const ACCOUNT_TYPE_GMAIL = 'gmail';
    public const ACCOUNT_TYPE_MICROSOFT = 'microsoft';
    public const ACCOUNT_TYPE_OTHER = 'other';
    public const ACCOUNT_TYPE_NO_SELECT = 'selectType';

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
     * @param UserEmailOrigin|null $value
     */
    public function setUserEmailOrigin($value)
    {
        if ($value) {
            if ($value->getAccountType() === self::ACCOUNT_TYPE_OTHER) {
                $value->setAccessToken(null);
                $value->setRefreshToken(null);
                $value->setAccessTokenExpiresAt(null);
            } else {
                $value->setPassword(null);
            }
        }
        $this->userEmailOrigin = $value;
    }
}
