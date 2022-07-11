<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Sync;

use Oro\Bundle\EmailBundle\Sync\EmailSyncNotificationAlert;

class EmailSyncNotificationAlertTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSourceType(): void
    {
        $alert = EmailSyncNotificationAlert::createForAuthFail();
        self::assertEquals('EmailSync', $alert->getSourceType());
    }

    public function testGetId(): void
    {
        $alert = EmailSyncNotificationAlert::createForAuthFail();
        self::assertIsString($alert->getId());
    }

    public function testCreateForAuthFail(): void
    {
        $organizationId = 12;
        $originId = 101;

        $alert = EmailSyncNotificationAlert::createForAuthFail('Auth fail.');
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'auth',
                'operation'       => 'import',
                'user'            => null,
                'organization'    => $organizationId,
                'message'         => 'Auth fail.',
                'additionalInfo'  => ['emailOriginId' => 101]
            ],
            $data
        );
    }

    public function testCreateForRefreshTokenFail(): void
    {
        $organizationId = 13;
        $originId = 5;

        $alert = EmailSyncNotificationAlert::createForRefreshTokenFail('Refresh token fail.');
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'refresh token',
                'operation'       => 'import',
                'user'            => null,
                'organization'    => $organizationId,
                'message'         => 'Refresh token fail.',
                'additionalInfo'  => ['emailOriginId' => 5]
            ],
            $data
        );
    }

    public function testCreateForGetListFail(): void
    {
        $userId = 1;
        $organizationId = 13;
        $originId = 102;

        $alert = EmailSyncNotificationAlert::createForGetListFail('Get list fail.');
        $alert->setUserId($userId);
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'sync',
                'operation'       => 'import',
                'user'            => 1,
                'organization'    => 13,
                'step'            => 'get list',
                'message'         => 'Get list fail.',
                'additionalInfo'  => ['emailOriginId' => 102]
            ],
            $data
        );
    }

    public function testCreateForGetItemBodyFail(): void
    {
        $userId = 2;
        $organizationId = 14;
        $originId = 103;

        $alert = EmailSyncNotificationAlert::createForGetItemBodyFail(12, 'Get item fail.');
        $alert->setUserId($userId);
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'sync',
                'operation'       => 'import body',
                'user'            => 2,
                'organization'    => 14,
                'step'            => 'get',
                'message'         => 'Get item fail.',
                'additionalInfo'  => ['emailOriginId' => 103, 'emailId' => 12]
            ],
            $data
        );
    }

    public function testCreateForConvertFailed(): void
    {
        $userId = 3;
        $organizationId = 15;
        $originId = 104;

        $alert = EmailSyncNotificationAlert::createForConvertFailed('Convert item fail.', ['some_data' => 'test']);
        $alert->setUserId($userId);
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'sync',
                'operation'       => 'import',
                'user'            => 3,
                'organization'    => 15,
                'step'            => 'convert',
                'message'         => 'Convert item fail.',
                'additionalInfo'  => ['emailOriginId' => 104, 'some_data' => 'test']
            ],
            $data
        );
    }

    public function testCreateForSwitchFolderFail(): void
    {
        $userId = 6;
        $organizationId = 17;
        $originId = 107;

        $alert = EmailSyncNotificationAlert::createForSwitchFolderFail('Switch folder failed.', 'test folder', 167);
        $alert->setUserId($userId);
        $alert->setOrganizationId($organizationId);
        $alert->setEmailOriginId($originId);

        $data = $alert->toArray();
        self::assertIsString($data['id']);
        unset($data['id']);
        self::assertEquals(
            [
                'sourceType'      => 'EmailSync',
                'resourceType'    => 'email',
                'alertType'       => 'switch folder',
                'operation'       => 'import',
                'user'            => 6,
                'organization'    => 17,
                'message'         => 'Switch folder failed.',
                'additionalInfo'  => [
                    'emailOriginId' => 107,
                    'folderName'    => 'test folder',
                    'folderId'      => 167
                ]
            ],
            $data
        );
    }
}
