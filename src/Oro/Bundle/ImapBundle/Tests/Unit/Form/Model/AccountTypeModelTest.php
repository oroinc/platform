<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Model;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

class AccountTypeModelTest extends \PHPUnit\Framework\TestCase
{
    public function testAccountType(): void
    {
        $model = new AccountTypeModel();
        self::assertNull($model->getAccountType());
        $model->setAccountType(AccountTypeModel::ACCOUNT_TYPE_OTHER);
        self::assertEquals(AccountTypeModel::ACCOUNT_TYPE_OTHER, $model->getAccountType());
    }

    public function testSetUserEmailOriginWithOtherAccountType(): void
    {
        $model = new AccountTypeModel();
        $origin = new UserEmailOrigin();
        $origin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_OTHER);
        $origin->setAccessToken('test_token');
        $origin->setRefreshToken('refresh_token');
        $origin->setAccessTokenExpiresAt(new \DateTime());

        self::assertNull($model->getUserEmailOrigin());
        $model->setUserEmailOrigin($origin);

        self::assertSame($origin, $model->getUserEmailOrigin());
        self::assertNull($origin->getAccessToken());
        self::assertNull($origin->getRefreshToken());
        self::assertNull($origin->getAccessTokenExpiresAt());
    }

    public function testSetUserEmailOriginWithNonOtherAccountType(): void
    {
        $model = new AccountTypeModel();
        $origin = new UserEmailOrigin();
        $origin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT);
        $origin->setPassword('test_password');

        self::assertNull($model->getUserEmailOrigin());
        $model->setUserEmailOrigin($origin);

        self::assertSame($origin, $model->getUserEmailOrigin());
        self::assertNull($origin->getPassword());
    }
}
