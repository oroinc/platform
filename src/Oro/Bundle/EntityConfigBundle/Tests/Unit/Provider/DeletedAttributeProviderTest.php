<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Provider\AttributeValueProviderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProvider;

class DeletedAttributeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $configModelManager;

    /**
     * @var AttributeValueProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeValueProvider;

    /**
     * @var DeletedAttributeProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->configModelManager = $this->getMockBuilder(ConfigModelManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeValueProvider = $this->createMock(AttributeValueProviderInterface::class);

        $this->provider = new DeletedAttributeProvider($this->configModelManager, $this->attributeValueProvider);
    }

    public function testGetAttributesByIdsDbFailed()
    {
        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn(false);
        $this->configModelManager->expects($this->never())
            ->method('getEntityManager');

        $attributes = $this->provider->getAttributesByIds([333]);
        $this->assertEmpty($attributes);
    }

    public function testGetAttributesByIds()
    {
        $ids = [333];
        $this->configModelManager->expects($this->once())
            ->method('checkDatabase')
            ->willReturn(true);
        $repository = $this->getMockBuilder(FieldConfigModelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testRemoveAttributeValuesWithoutNames()
    {
        $attributeFamily = new AttributeFamily();
        $this->attributeValueProvider->expects($this->never())
            ->method('removeAttributeValues');

        $this->provider->removeAttributeValues(
            $attributeFamily,
            []
        );
    }
    
    public function testRemoveAttributeValues()
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
