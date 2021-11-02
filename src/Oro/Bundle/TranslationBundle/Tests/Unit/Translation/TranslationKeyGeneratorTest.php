<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;

class TranslationKeyGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationKeyGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new TranslationKeyGenerator();
    }

    /**
     * @dataProvider generateDataProvider
     */
    public function testGenerate(array $data, string $expected)
    {
        $keySource = $this->createMock(TranslationKeySourceInterface::class);
        $keySource->expects($this->once())
            ->method('getTemplate')
            ->willReturn('test.{{ data }}.test');
        $keySource->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->assertEquals($expected, $this->generator->generate($keySource));
    }

    public function generateDataProvider(): array
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
