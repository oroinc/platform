<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Encoder;

use Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderRegistry;

class ExpressionEncoderRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var ExpressionEncoderRegistry */
    protected $encoderRegistry;

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->encoder   = $this->getMock('Oro\Bundle\LayoutBundle\Layout\Encoder\ExpressionEncoderInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->with('test_encoder_service')
            ->will($this->returnValue($this->encoder));

        $this->encoderRegistry = new ExpressionEncoderRegistry(
            $this->container,
            ['test' => 'test_encoder_service']
        );
    }

    public function testGetEncoder()
    {
        $this->assertSame($this->encoder, $this->encoderRegistry->getEncoder('test'));
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The expression encoder for "unknown" formatting was not found. Check that the appropriate encoder service is registered in the container and marked by tag "oro_layout.expression.encoder".
     */
    // @codingStandardsIgnoreEnd
    public function testGetEncoderThrowsExceptionIfEncoderDoesNotExist()
    {
        $this->encoderRegistry->getEncoder('unknown');
    }
}
