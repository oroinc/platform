<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\ContextEmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\UserBundle\Entity\User;

class ContextEmailRecipientsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RelatedEmailsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $relatedEmailsProvider;

    /** @var ContextEmailRecipientsProvider */
    private $emailRecipientsProvider;

    protected function setUp(): void
    {
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);

        $this->emailRecipientsProvider = new ContextEmailRecipientsProvider($this->relatedEmailsProvider);
    }

    public function testGetSectionShouldReturnContextSection()
    {
        $this->assertEquals('oro.email.autocomplete.contexts', $this->emailRecipientsProvider->getSection());
    }

    public function testGetRecipientsShouldReturnEmptyArrayIfRelatedEntityIsNull()
    {
        $args = new EmailRecipientsProviderArgs(null, 'em', 100);
        $this->assertEmpty($this->emailRecipientsProvider->getRecipients($args));
    }

    /**
     * @dataProvider argsProvider
     */
    public function testGetRecipientsShouldReturnRecipients(
        EmailRecipientsProviderArgs $args,
        array $relatedEmails,
        array $expectedRecipients
    ) {
        $this->relatedEmailsProvider->expects($this->once())
            ->method('getRecipients')
            ->willReturn($relatedEmails);

        $this->assertEquals($expectedRecipients, $this->emailRecipientsProvider->getRecipients($args));
    }

    public function argsProvider(): array
    {
        return [
            [
                new EmailRecipientsProviderArgs(new User(), 're', 100),
                [
                    new Recipient('related@example.com', 'Related <related@example.com>'),
                ],
                [
                    new Recipient('related@example.com', 'Related <related@example.com>'),
                ],
            ],
            [
                new EmailRecipientsProviderArgs(new User(), 're', 100, [new Recipient('related@example.com', 'name')]),
                [
                    'related@example.com' => new Recipient('related@example.com', 'Related <related@example.com>'),
                    'related2@example.com' => new Recipient('related2@example.com', 'Related2 <related2@example.com>'),
                ],
                [
                    'related2@example.com' => new Recipient('related2@example.com', 'Related2 <related2@example.com>'),
                ],
            ],
            [
                new EmailRecipientsProviderArgs(new User(), 'res', 100),
                [
                    new Recipient('related@example.com', 'Related <related@example.com>'),
                ],
                [],
            ],
        ];
    }
}
