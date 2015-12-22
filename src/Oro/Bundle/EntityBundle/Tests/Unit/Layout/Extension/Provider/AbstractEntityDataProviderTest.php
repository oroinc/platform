<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Layout\Extension\Provider;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\EntityBundle\Layout\Extension\Provider\AbstractEntityDataProvider;

class AbstractEntityDataProviderTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_FQCN = 'Oro\Bundle\TestBundle\Entity\TestEntityRepository';
    const ENTITY_ALIAS = 'testEntity';
    const ENTITY_ID = 12;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\ORM\EntityRepository  */
    protected $objectRepository;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ObjectManager */
    protected $objectManager;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|\Doctrine\Common\Persistence\ManagerRegistry */
    protected $managerRegistry;

    /** @var AbstractEntityDataProvider */
    protected $provider;

    public function setUp()
    {
        $this->objectRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_FQCN)
            ->willReturn($this->objectRepository);

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_FQCN)
            ->willReturn($this->objectManager);

        $this->provider = new AbstractEntityDataProvider($this->managerRegistry);
        $this->provider->setEntityFQCN(self::ENTITY_FQCN);
        $this->provider->setContextIdAlias(self::ENTITY_ALIAS);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context[testEntity] should be specified.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    public function testGetData()
    {
        $entityMock = new \stdClass();

        $context = new LayoutContext();
        $context->set(self::ENTITY_ALIAS, self::ENTITY_ID);

        $this->objectRepository->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_ID)
            ->willReturn($entityMock);

        $actual = $this->provider->getData($context);

        $this->assertEquals($entityMock, $actual);
    }
}
