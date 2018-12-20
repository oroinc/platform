<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AttributeFamilyVoterTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS_NAME = 'stdClass';
    const FAMILY_ID = 777;

    use EntityTrait;

    /**
     * @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $token;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var AttributeFamilyManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $familyManager;

    /**
     * @var AttributeFamilyVoter
     */
    private $voter;

    protected function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->familyManager = $this->getMockBuilder(AttributeFamilyManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new AttributeFamilyVoter($this->doctrineHelper, $this->familyManager);
    }

    /**
     * @param AttributeFamily $attributeFamily
     */
    private function configureDocrineHelperExpectations(AttributeFamily $attributeFamily)
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($attributeFamily, false)
            ->willReturn(self::FAMILY_ID);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->with($attributeFamily)
            ->willReturn(AttributeFamily::class);
    }

    public function testVoteWithNotSupportedClass()
    {
        $this->assertEquals(
            AbstractEntityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass, ['delete'])
        );
    }

    public function testVoteWithNotSupportedAttribute()
    {
        $this->assertEquals(
            AbstractEntityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, new \stdClass, ['view'])
        );
    }

    public function testVoteWhenAttributeFamilyDeniedToDelete()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $this->configureDocrineHelperExpectations($attributeFamily);

        $this->familyManager
            ->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(self::FAMILY_ID)
            ->willReturn(false);

        $this->assertEquals(
            AbstractEntityVoter::ACCESS_DENIED,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }

    public function testVoteWhenAttributeFamilyNotLastAndHasNoEntityAssignedToIt()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $this->configureDocrineHelperExpectations($attributeFamily);
        $this->familyManager
            ->expects($this->once())
            ->method('isAttributeFamilyDeletable')
            ->with(self::FAMILY_ID)
            ->willReturn(true);

        $this->assertEquals(
            AbstractEntityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }
}
