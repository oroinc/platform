<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const FAMILY_ID = 777;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttributeFamilyManager */
    private $familyManager;

    /** @var AttributeFamilyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $familyRepository;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var AttributeFamily */
    private $attributeFamily;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->familyRepository = $this->getMockBuilder(AttributeFamilyRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => 'SomeClass'
        ]);

        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->familyRepository);

        $this->familyRepository->expects($this->once())
            ->method('find')
            ->with(self::FAMILY_ID)
            ->willReturn($this->attributeFamily);

        $this->familyManager = new AttributeFamilyManager($this->doctrineHelper);
    }

    public function testFamilyIsLast()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->familyRepository);

        $this->familyRepository->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with('SomeClass')
            ->willReturn(1);

        $this->assertFalse($this->familyManager->isAttributeFamilyDeletable(self::FAMILY_ID));
    }

    public function testFamilyHasAssignedEntities()
    {
        $this->familyRepository->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with('SomeClass')
            ->willReturn(3);

        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityRepository')
            ->with('SomeClass')
            ->willReturn($this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['attributeFamily' => $this->attributeFamily])
            ->willReturn(new \stdClass());

        $this->assertFalse($this->familyManager->isAttributeFamilyDeletable(self::FAMILY_ID));
    }

    public function testFamilyIsDeletable()
    {
        $this->familyRepository->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with('SomeClass')
            ->willReturn(3);

        $this->doctrineHelper->expects($this->at(1))
            ->method('getEntityRepository')
            ->with('SomeClass')
            ->willReturn($this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['attributeFamily' => $this->attributeFamily])
            ->willReturn(null);

        $this->assertTrue($this->familyManager->isAttributeFamilyDeletable(self::FAMILY_ID));
    }
}
