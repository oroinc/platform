<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\EmailRecipientsProvider;

class EmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRecipientsHelper;

    /** @var EmailRecipientsProvider */
    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $this->emailRecipientsProvider = new EmailRecipientsProvider(
            $this->doctrine,
            $this->emailRecipientsHelper
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $userRepository = $this->createMock(UserRepository::class);

        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->emailRecipientsHelper->expects($this->once())
            ->method('getRecipients')
            ->with($args, $userRepository, 'u', User::class)
            ->willReturn($recipients);

        $this->assertEquals($recipients, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function dataProvider(): array
    {
        return [
            [
                new EmailRecipientsProviderArgs(null, null, 1),
                [
                    'recipient@example.com'  => 'Recipient <recipient@example.com>',
                ],
            ],
        ];
    }
}
