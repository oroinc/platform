<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use JMS\Serializer\SerializerInterface;
use Oro\Bundle\PlatformBundle\Twig\SerializerExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class SerializerExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var SerializerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $serializer;

    /** @var SerializerExtension */
    private $extension;

    protected function setUp(): void
    {
        if (!interface_exists('JMS\Serializer\SerializerInterface')) {
            self::markTestSkipped('"jms/serializer" is not installed');
        }

        $this->serializer = $this->createMock(SerializerInterface::class);

        $container = self::getContainerBuilder()
            ->add('jms_serializer', $this->serializer)
            ->getContainer($this);

        $this->extension = new SerializerExtension($container);
    }

    public function testSerialize()
    {
        $obj = new \stdClass();
        $serializedData = 'serialized';

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->with(self::identicalTo($obj), 'json')
            ->willReturn($serializedData);

        self::assertEquals(
            $serializedData,
            self::callTwigFilter($this->extension, 'serialize', [$obj])
        );
    }
}
