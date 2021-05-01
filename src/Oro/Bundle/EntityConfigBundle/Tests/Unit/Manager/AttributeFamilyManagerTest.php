<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const FAMILY_ID = 777;

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

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->familyRepository = $this->createMock(AttributeFamilyRepository::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $this->attributeFamily = $this->getEntity(AttributeFamily::class, [
            'id' => self::FAMILY_ID,
            'entityClass' => 'SomeClass'
        ]);

        $this->familyRepository->expects($this->any())
            ->method('find')
            ->with(self::FAMILY_ID)
            ->willReturn($this->attributeFamily);

        $this->familyManager = new AttributeFamilyManager($this->doctrineHelper);
    }

    public function testFamilyIsLast(): void
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

    public function testFamilyHasAssignedEntities(): void
    {
        $this->familyRepository->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with('SomeClass')
            ->willReturn(3);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([AttributeFamily::class], ['SomeClass'])
            ->willReturnOnConsecutiveCalls($this->familyRepository, $this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['attributeFamily' => $this->attributeFamily])
            ->willReturn(new \stdClass());

        $this->assertFalse($this->familyManager->isAttributeFamilyDeletable(self::FAMILY_ID));
    }

    public function testFamilyIsDeletable(): void
    {
        $this->familyRepository->expects($this->once())
            ->method('countFamiliesByEntityClass')
            ->with('SomeClass')
            ->willReturn(3);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive([AttributeFamily::class], ['SomeClass'])
            ->willReturnOnConsecutiveCalls($this->familyRepository, $this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['attributeFamily' => $this->attributeFamily])
            ->willReturn(null);

        $this->assertTrue($this->familyManager->isAttributeFamilyDeletable(self::FAMILY_ID));
    }

    public function testGetAttributeFamilyByCode(): void
    {
        $aclHelper = $this->createMock(AclHelper::class);

        $this->familyManager->setAclHelper($aclHelper);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($this->familyRepository);
        $this->familyRepository->expects($this->once())
            ->method('getFamilyByCode')
            ->with('test', $aclHelper)
            ->willReturn($this->attributeFamily);

        $this->assertSame($this->attributeFamily, $this->familyManager->getAttributeFamilyByCode('test'));

        // check local cache
        $this->assertSame($this->attributeFamily, $this->familyManager->getAttributeFamilyByCode('test'));
    }
}
