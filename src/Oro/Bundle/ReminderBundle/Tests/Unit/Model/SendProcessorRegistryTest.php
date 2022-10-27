<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Exception\MethodNotSupportedException;
use Oro\Bundle\ReminderBundle\Model\SendProcessorInterface;
use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SendProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    private const FOO_METHOD = 'foo';
    private const FOO_LABEL  = 'foo_label';
    private const BAR_METHOD = 'bar';
    private const BAR_LABEL  = 'bar_label';

    /** @var SendProcessorInterface[]|\PHPUnit\Framework\MockObject\MockObject[] */
    private $processors;

    /** @var SendProcessorRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->processors = [];
        $this->processors[self::FOO_METHOD] = $this->getMockProcessor(self::FOO_LABEL);
        $this->processors[self::BAR_METHOD] = $this->getMockProcessor(self::BAR_LABEL);

        $containerBuilder = TestContainerBuilder::create();
        foreach ($this->processors as $method => $processor) {
            $containerBuilder->add($method, $processor);
        }

        $this->registry = new SendProcessorRegistry(
            [self::FOO_METHOD, self::BAR_METHOD],
            $containerBuilder->getContainer($this)
        );
    }

    private function getMockProcessor(string $label): SendProcessorInterface
    {
        $result = $this->createMock(SendProcessorInterface::class);
        $result->expects($this->any())
            ->method('getLabel')
            ->willReturn($label);

        return $result;
    }

    public function testGetProcessors()
    {
        $this->assertEquals(
            [
                self::FOO_METHOD => $this->processors[self::FOO_METHOD],
                self::BAR_METHOD => $this->processors[self::BAR_METHOD]
            ],
            $this->registry->getProcessors()
        );
    }

    public function testGetProcessor()
    {
        $this->assertEquals($this->processors[self::FOO_METHOD], $this->registry->getProcessor(self::FOO_METHOD));
        $this->assertEquals($this->processors[self::BAR_METHOD], $this->registry->getProcessor(self::BAR_METHOD));
    }

    public function testGetProcessorFails()
    {
        $this->expectException(MethodNotSupportedException::class);
        $this->expectExceptionMessage('Reminder method "not_exist" is not supported.');

        $this->registry->getProcessor('not_exist');
    }
}
