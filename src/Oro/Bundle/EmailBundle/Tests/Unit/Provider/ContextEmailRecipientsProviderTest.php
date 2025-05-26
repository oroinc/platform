<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Provider\ContextEmailRecipientsProvider;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContextEmailRecipientsProviderTest extends TestCase
{
    private RelatedEmailsProvider&MockObject $relatedEmailsProvider;
    private ContextEmailRecipientsProvider $emailRecipientsProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);

        $this->emailRecipientsProvider = new ContextEmailRecipientsProvider($this->relatedEmailsProvider);
    }

    public function testGetSectionShouldReturnContextSection(): void
    {
        $this->assertEquals('oro.email.autocomplete.contexts', $this->emailRecipientsProvider->getSection());
    }

    public function testGetRecipientsShouldReturnEmptyArrayIfRelatedEntityIsNull(): void
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
    ): void {
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
