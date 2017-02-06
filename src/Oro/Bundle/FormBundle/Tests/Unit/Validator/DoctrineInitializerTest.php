<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Validator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\Form\Util\OrderedHashMap;

use Oro\Bundle\FormBundle\Validator\DoctrineInitializer;

class DoctrineInitializerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var DoctrineInitializer */
    protected $doctrineInitializer;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineInitializer = new DoctrineInitializer($this->doctrine);
    }

    /**
     * @dataProvider predefinedNotManageableObjectsProvider
     */
    public function testInitializeForPredefinedNotManageableObjects($object)
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $this->doctrineInitializer->initialize($object);
    }

    public function predefinedNotManageableObjectsProvider()
    {
        return [
            [$this->getMockBuilder(FormInterface::class)->disableOriginalConstructor()->getMock()],
            [$this->getMockBuilder(OrderedHashMap::class)->disableOriginalConstructor()->getMock()],
        ];
    }

    public function testInitializeForNotPredefinedObject()
    {
        $object = new \stdClass();

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(get_class($object))
            ->willReturn(null);

        $this->doctrineInitializer->initialize($object);
    }
}
