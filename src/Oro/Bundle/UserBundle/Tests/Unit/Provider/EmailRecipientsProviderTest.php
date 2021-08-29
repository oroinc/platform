<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\EmailRecipientsProvider;

class EmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmailRecipientsHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRecipientsHelper;

    /** @var EmailRecipientsProvider */
    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->emailRecipientsHelper = $this->createMock(EmailRecipientsHelper::class);

        $this->emailRecipientsProvider = new EmailRecipientsProvider(
            $this->registry,
            $this->emailRecipientsHelper
        );
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $userRepository = $this->createMock(UserRepository::class);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroUserBundle:User')
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
