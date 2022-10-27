<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Entity;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\User;
use Oro\Bundle\OrganizationBundle\Twig\OrganizationExtension;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class OrganizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var BusinessUnitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $businessUnitManager;

    /** @var OrganizationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->businessUnitManager = $this->createMock(BusinessUnitManager::class);

        $entityOwnerAccessor = $this->createMock(EntityOwnerAccessor::class);
        $entityOwnerAccessor->expects($this->any())
            ->method('getOwner')
            ->willReturnCallback(function ($entity) {
                return $entity->getOwner();
            });

        $container = self::getContainerBuilder()
            ->add(ConfigManager::class, $this->configManager)
            ->add('oro_security.owner.entity_owner_accessor', $entityOwnerAccessor)
            ->add('oro_organization.business_unit_manager', $this->businessUnitManager)
            ->getContainer($this);

        $this->extension = new OrganizationExtension($container);
    }

    public function testGetOwnerType()
    {
        $entity = new BusinessUnit();
        $className = get_class($entity);
        $entityConfig = new Config(new EntityConfigId('ownership', $className));
        $entityConfig->setValues(['owner_type' => 'test_type']);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $className)
            ->willReturn($entityConfig);

        $this->assertEquals(
            'test_type',
            self::callTwigFunction($this->extension, 'oro_get_owner_type', [$entity])
        );
    }

    public function testWithoutOwnerType()
    {
        $entity = new BusinessUnit();
        $className = get_class($entity);
        $entityConfig = new Config(new EntityConfigId('ownership', $className));
        $entityConfig->setValues(['another_owner_type' => 'test_type']);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $className)
            ->willReturn($entityConfig);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_get_owner_type', [$entity])
        );
    }

    public function testGetEntityOwner()
    {
        $owner = new User();
        $entity = new Entity();
        $entity->setOwner($owner);

        $this->assertSame(
            $owner,
            self::callTwigFunction($this->extension, 'oro_get_entity_owner', [$entity])
        );
    }

    public function testGetOwnerFieldName()
    {
        $entity = new BusinessUnit();
        $className = get_class($entity);
        $entityConfig = new Config(new EntityConfigId('ownership', $className));
        $entityConfig->setValues(['owner_field_name' => 'test_field']);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);
        $this->configManager->expects($this->once())
            ->method('getEntityConfig')
            ->with('ownership', $className)
            ->willReturn($entityConfig);

        $this->assertEquals(
            'test_field',
            self::callTwigFunction($this->extension, 'oro_get_owner_field_name', [$entity])
        );
    }

    public function testGetBusinessUnitCount()
    {
        $repo = $this->createMock(BusinessUnitRepository::class);
        $repo->expects($this->once())
            ->method('getBusinessUnitsCount')
            ->willReturn(2);
        $this->businessUnitManager->expects($this->once())
            ->method('getBusinessUnitRepo')
            ->willReturn($repo);

        $this->assertEquals(
            2,
            self::callTwigFunction($this->extension, 'oro_get_business_units_count', [])
        );
    }
}
