<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Translation\KeySource\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Translation\TranslationKeyGenerator;

class TranslationKeyGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationKeyGenerator */
    protected $generator;

    /** @var TranslationKeySourceInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $keySource;

    protected function setUp()
    {
        $this->generator = new TranslationKeyGenerator();

        $this->keySource = $this->getMock(TranslationKeySourceInterface::class);
    }

    /**
     * @dataProvider generateDataProvider
     *
     * @param array $data
     * @param string $expected
     */
    public function testGenerate(array $data, $expected)
    {
        $this->keySource->expects($this->once())->method('getTemplate')->willReturn('test.{{ data }}.test');
        $this->keySource->expects($this->once())->method('getData')->willReturn($data);

        $this->assertEquals($expected, $this->generator->generate($this->keySource));
    }

    /**
     * @return array
     */
    public function generateDataProvider()
    {
        return [
            'trim string' => [
                'data' => [
                    'data' => ' value'
                ],
                'expected' => 'test.value.test'
            ],
            'camelized string' => [
                'data' => [
                    'data' => ' Value'
                ],
                'expected' => 'test.value.test'
            ],
            'camelcase string' => [
                'data' => [
                    'data' => ' MyValue'
                ],
                'expected' => 'test.my_value.test'
            ],
            'string with number' => [
                'data' => [
                    'data' => ' MyValue1'
                ],
                'expected' => 'test.my_value1.test'
            ],
            'string with special chars' => [
                'data' => [
                    'data' => ' MyValue1 %$# 3'
                ],
                'expected' => 'test.my_value1_%$#_3.test'
            ],
            'string with whitespaces' => [
                'data' => [
                    'data' => ' My Value With    Spaces 1'
                ],
                'expected' => 'test.my_value_with_spaces_1.test'
            ],
        ];
    }
}
