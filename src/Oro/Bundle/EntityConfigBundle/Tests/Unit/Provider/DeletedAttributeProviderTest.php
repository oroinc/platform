<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletedAttributeProviderTest extends TestCase
{
    private ConfigModelManager&MockObject $configModelManager;
    private AttributeValueProviderInterface&MockObject $attributeValueProvider;
    private DeletedAttributeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configModelManager = $this->createMock(ConfigModelManager::class);
        $this->attributeValueProvider = $this->createMock(AttributeValueProviderInterface::class);

        $this->provider = new DeletedAttributeProvider($this->configModelManager, $this->attributeValueProvider);
    }

    public function testGetAttributesByIdsDbFailed(): void
    {
        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn(false);
        $this->configModelManager->expects($this->never())
            ->method('getEntityManager');

        $attributes = $this->provider->getAttributesByIds([333]);
        $this->assertEmpty($attributes);
    }

    public function testGetAttributesByIds(): void
    {
        $ids = [333];
        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn(true);
        $repository = $this->createMock(FieldConfigModelRepository::class);
        $repository->expects($this->once())
            ->method('getAttributesByIds')
            ->with($ids)
            ->willReturn([new FieldConfigModel()]);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(FieldConfigModel::class)
            ->willReturn($repository);
        $this->configModelManager->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $attributes = $this->provider->getAttributesByIds($ids);
        $this->assertNotEmpty($attributes);
    }

    public function testRemoveAttributeValuesWithoutNames(): void
    {
        $attributeFamily = new AttributeFamily();
        $this->attributeValueProvider->expects($this->never())
            ->method('removeAttributeValues');

        $this->provider->removeAttributeValues(
            $attributeFamily,
            []
        );
    }

    public function testRemoveAttributeValues(): void
    {
        $attributeFamily = new AttributeFamily();
        $names = ['names'];
        $this->attributeValueProvider->expects($this->once())
            ->method('removeAttributeValues')
            ->with($attributeFamily, $names);

        $this->provider->removeAttributeValues(
            $attributeFamily,
            $names
        );
    }
}
