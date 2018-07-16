<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Condition;

use Oro\Bundle\ConfigBundle\Condition\IsSystemConfigEqual;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class IsSystemConfigEqualTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var IsSystemConfigEqual */
    protected $condition;

    public function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->condition = new IsSystemConfigEqual($this->configManager);
    }

    public function testGetName()
    {
        $this->assertEquals(IsSystemConfigEqual::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider initializeDataProvider
     * @param array $options
     * @param $message
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     */
    public function testInitializeExceptions(array $options, $message)
    {
        $this->expectExceptionMessage($message);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            [
                'options' => [1, 2, 3],
                'exceptionMessage' => 'Options must have 2 elements, but 3 given.',
            ],
            [
                'options' => [],
                'exceptionMessage' => 'Options must have 2 elements, but 0 given.',
            ],
            [
                'options' => [1 => 1, 2 => 2],
                'exceptionMessage' => 'Option "key" is required.',
            ],
            [
                'options' => ['key' => 'test_key', 'wrong_option' => 'key2'],
                'exceptionMessage' => 'Option "value" is required.',
            ],
        ];
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param mixed $configValue
     * @param mixed $value
     * @param bool $expected
     */
    public function testEvaluate($configValue, $value, $expected)
    {
        $this->configManager->expects($this->once())->method('get')->with('test_key')->willReturn($configValue);
        $this->condition->initialize(['key' => 'test_key', 'value' => $value]);
        $this->assertEquals($expected, $this->condition->evaluate([]));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            'string values not equal' => [
                'configValue' => 'value1',
                'value' => 'value2',
                'expected' => false,
            ],
            'string values equal' => [
                'configValue' => 'value1',
                'value' => 'value1',
                'expected' => true,
            ],
            'array values not equal' => [
                'configValue' => ['value1'],
                'value' => ['value1', 'value2'] ,
                'expected' => false,
            ],
            'array values equal' => [
                'configValue' => ['value1'],
                'value' => ['value1'],
                'expected' => true,
            ],
            'array string values' => [
                'configValue' => ['value1'],
                'value' => 'value1',
                'expected' => false,
            ],
            'int string values' => [
                'configValue' => 1,
                'value' => '1',
                'expected' => false,
            ],
        ];
    }
}
