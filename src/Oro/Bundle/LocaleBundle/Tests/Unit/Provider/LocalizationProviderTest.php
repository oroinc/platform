<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var  LocalizationProvider */
    protected $provider;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocalizationProvider($this->registry);
    }

    public function tearDown()
    {
        unset($this->registry, $this->manager, $this->registry, $this->provider);
    }

    public function testGetLocalization()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

        $this->assertRegistryCalled();

        $this->repository->expects($this->once())
            ->method('find')
            ->with($entity->getId())
            ->willReturn($entity);

        $result = $this->provider->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizations()
    {
        /** @var Localization[] $entities */
        $entities = [
            $this->getEntity(Localization::class, ['id' => 1]),
            $this->getEntity(Localization::class, ['id' => 2]),
            $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $this->assertRegistryCalled();

        $this->repository->expects($this->once())
            ->method('findAll')
            ->willReturn($entities);

        $result = $this->provider->getLocalizations();

        $this->assertEquals($entities, $result);
    }

    protected function assertRegistryCalled()
    {

        $this->manager->expects($this->once())
            ->method('getRepository')
            ->with(Localization::class)
            ->willReturn($this->repository);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Localization::class)
            ->willReturn($this->manager);
    }
}
