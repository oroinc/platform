<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Oro\Bundle\PlatformBundle\Twig\SerializerExtension;

class SerializerExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var SerializerExtension */
    protected $extension;

    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    protected function setUp()
    {
        $this->serializer = $this->createMock(\JMS\Serializer\Twig\SerializerExtension::class);
        $this->container  = self::getContainerBuilder()
            ->add('jms_serializer', $this->serializer)
            ->getContainer($this);

        $this->extension = new SerializerExtension($this->container);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'jms_serializer',
            $this->extension->getName()
        );
    }

    public function testSerialize()
    {
        $obj = new \stdClass();

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('jms_serializer');

        $this->serializer
            ->expects($this->once())
            ->method('serialize')
            ->with($this->equalTo($obj), $this->equalTo('json'));

        $this->extension->serialize($obj);
    }
}
