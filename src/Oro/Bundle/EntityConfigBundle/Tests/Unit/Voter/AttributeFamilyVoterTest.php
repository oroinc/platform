<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AttributeFamilyVoterTest extends TestCase
{
    use EntityTrait;

    private const ENTITY_CLASS_NAME = 'stdClass';
    private const FAMILY_ID = 777;

    private TokenInterface&MockObject $token;
    private DoctrineHelper&MockObject $doctrineHelper;
    private AttributeFamilyManager&MockObject $attributeFamilyManager;
    private AttributeFamilyVoter $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->attributeFamilyManager = $this->createMock(AttributeFamilyManager::class);

        $container = TestContainerBuilder::create()
            ->add(AttributeFamilyManager::class, $this->attributeFamilyManager)
            ->getContainer($this);

        $this->voter = new AttributeFamilyVoter($this->doctrineHelper, $container);
        $this->voter->setClassName(AttributeFamily::class);
    }

    public function testVoteWithNotSupportedClass(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), ['delete'])
        );
    }

    public function testVoteWithNotSupportedAttribute(): void
    {
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass(), ['view'])
        );
    }

    public function testVoteWhenAttributeFamilyDeniedToDelete(): void
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($attributeFamily, false)
            ->willReturn(self::FAMILY_ID);

        $this->attributeFamilyManager->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(self::FAMILY_ID)
            ->willReturn(false);

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }

    public function testVoteWhenAttributeFamilyNotLastAndHasNoEntityAssignedToIt(): void
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($attributeFamily, false)
            ->willReturn(self::FAMILY_ID);

        $this->attributeFamilyManager->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(self::FAMILY_ID)
            ->willReturn(true);

        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }
}
