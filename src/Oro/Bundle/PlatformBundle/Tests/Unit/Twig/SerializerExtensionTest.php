<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Twig;

use JMS\Serializer\SerializerInterface;
use Oro\Bundle\PlatformBundle\Twig\SerializerExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SerializerExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private SerializerInterface&MockObject $serializer;
    private SerializerExtension $extension;

    #[\Override]
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

    public function testSerialize(): void
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
