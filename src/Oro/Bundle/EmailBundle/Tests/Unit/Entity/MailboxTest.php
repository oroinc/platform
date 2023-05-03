<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\UserBundle\Entity\User;

class MailboxTest extends \PHPUnit\Framework\TestCase
{
    /** @var UserEmailOrigin|\PHPUnit\Framework\MockObject\MockObject */
    private $origin;

    protected function setUp(): void
    {
        $this->origin = $this->createMock(UserEmailOrigin::class);
        $this->origin->expects($this->any())
            ->method('getUser')
            ->willReturn($this->createMock(User::class));
    }

    /**
     * @dataProvider getAccountTypeDataProvider
     */
    public function testGetAccountType(bool $skipOrigin, string $accessToken, string $accountType)
    {
        $mailbox = new Mailbox();

        $mailbox->setOrigin($this->origin);
        if ($skipOrigin) {
            $mailbox->setOrigin(null);
            $this->assertEmpty($mailbox->getImapAccountType());
        } else {
            $this->origin->expects($this->any())
                ->method('getAccountType')
                ->willReturn($accountType);
            $this->origin->expects($this->any())
                ->method('getAccessToken')
                ->willReturn($accessToken);
            $this->assertEquals($accountType, $mailbox->getImapAccountType()->getAccountType());
        }
    }

    public function getAccountTypeDataProvider(): array
    {
        return [
            'empty origin' => [
                'skipOrigin' => true,
                'accessToken' => '',
                'accountType' => ''
            ],
            'expect Gmail account type' => [
                'skipOrigin' => false,
                'accessToken' => '12345',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_GMAIL
            ],
            'expect Other account type' => [
                'skipOrigin' => false,
                'accessToken' => '',
                'accountType' => AccountTypeModel::ACCOUNT_TYPE_OTHER
            ]
        ];
    }
}
