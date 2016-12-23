<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Voter;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Component\Testing\Unit\EntityTrait;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AttributeFamilyVoterTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS_NAME = 'stdClass';
    const FAMILY_ID = 777;

    use EntityTrait;

    /**
     * @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $token;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var \Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter
     */
    private $voter;

    protected function setUp()
    {
        $this->token = $this->createMock(TokenInterface::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->voter = new \Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter($this->doctrineHelper);
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param int $familiesCount
     * @return AttributeFamilyRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function expectsGetAttributeFamilyRepository(AttributeFamily $attributeFamily, $familiesCount)
    {
        $attributeFamilyRepository = $this->getMockBuilder(AttributeFamilyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $attributeFamilyRepository
            ->expects($this->once())
            ->method('find')
            ->with(self::FAMILY_ID)
            ->willReturn($attributeFamily);

        $attributeFamilyRepository
            ->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with(self::ENTITY_CLASS_NAME)
            ->willReturn($familiesCount);

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

        return $attributeFamilyRepository;
    }

    /**
     * @param mixed $returnedEntity
     * @param AttributeFamily $attributeFamily
     * @return EntityRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function expectsGetEntityRepository(AttributeFamily $attributeFamily, $returnedEntity)
    {
        $entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['attributeFamily' => $attributeFamily])
            ->willReturn($returnedEntity);

        return $entityRepository;
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

    public function testVoteWhenAttributeFamilyIsLast()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $attributeFamilyRepository = $this->expectsGetAttributeFamilyRepository($attributeFamily, 1);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository);

        $this->assertEquals(
            AbstractEntityVoter::ACCESS_DENIED,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }

    public function testVoteWhenAttributeFamilyNotLastAndHasEntityAssignedToIt()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => self::ENTITY_CLASS_NAME
        ]);

        $attributeFamilyRepository = $this->expectsGetAttributeFamilyRepository($attributeFamily, 2);
        $entityRepository = $this->expectsGetEntityRepository($attributeFamily, new \stdClass());

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([AttributeFamily::class], [self::ENTITY_CLASS_NAME])
            ->willReturnOnConsecutiveCalls($attributeFamilyRepository, $entityRepository);

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

        $attributeFamilyRepository = $this->expectsGetAttributeFamilyRepository($attributeFamily, 2);
        $entityRepository = $this->expectsGetEntityRepository($attributeFamily, null);

        $this->doctrineHelper
            ->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([AttributeFamily::class], [self::ENTITY_CLASS_NAME])
            ->willReturnOnConsecutiveCalls($attributeFamilyRepository, $entityRepository);

        $this->assertEquals(
            AbstractEntityVoter::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $attributeFamily, ['delete'])
        );
    }
}
