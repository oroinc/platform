<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var  LocalizationProvider */
    protected $provider;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocalizationProvider($this->repository);
    }

    public function tearDown()
    {
        unset($this->registry, $this->provider);
    }

    public function testGetLocalization()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

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

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn($entities);

        $result = $this->provider->getLocalizations();

        $this->assertEquals($entities, $result);
    }

    public function testGetLocalizationsByIds()
    {
        /** @var Localization[] $entities */
        $entities = [
            $this->getEntity(Localization::class, ['id' => 1]),
            $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $ids = [1, 3];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $ids])
            ->willReturn($entities);

        $result = $this->provider->getLocalizations((array)$ids);

        $this->assertEquals($entities, $result);
    }
}
