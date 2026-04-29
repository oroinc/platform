<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Acl\Voter\WebhookProducerSettingsVoter;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WebhookProducerSettingsVoterTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private WebhookProducerSettingsVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new WebhookProducerSettingsVoter($this->doctrineHelper);
        $this->voter->setClassName(WebhookProducerSettings::class);
    }

    public function testAbstainOnUnsupportedAttribute(): void
    {
        $webhook = new WebhookProducerSettings();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($webhook, false)
            ->willReturn('some-uuid');

        $token = $this->createMock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $webhook, ['EDIT'])
        );
    }

    public function testAbstainOnUnsupportedClass(): void
    {
        $object = new \stdClass();

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $token = $this->createMock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $object, ['DELETE'])
        );
    }

    public function testAbstainWhenWebhookIsNotSystem(): void
    {
        $webhook = new WebhookProducerSettings();
        $webhook->setSystem(false);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($webhook, false)
            ->willReturn('some-uuid');

        $token = $this->createMock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $webhook, ['DELETE'])
        );
    }

    public function testDeniedWhenWebhookIsSystem(): void
    {
        $webhook = new WebhookProducerSettings();
        $webhook->setSystem(true);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifier')
            ->with($webhook, false)
            ->willReturn('some-uuid');

        $token = $this->createMock(TokenInterface::class);

        self::assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $webhook, ['DELETE'])
        );
    }
}
