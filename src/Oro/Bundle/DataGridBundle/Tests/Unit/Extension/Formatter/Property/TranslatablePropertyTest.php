<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyConfiguration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\TranslatableProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatablePropertyTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatableProperty */
    private $property;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function (?string $key, array $params = []) {
                ksort($params);

                return trim(
                    sprintf(
                        'translated %s %s',
                        $key,
                        implode(
                            ', ',
                            array_map(
                                fn (?string $value, ?string $key) => sprintf('%s=%s', $key, $value),
                                $params,
                                array_keys($params)
                            )
                        )
                    )
                );
            });

        $this->property = new TranslatableProperty($translator);
    }

    /**
     * @dataProvider valueDataProvider
     */
    public function testGetRawValue(array $params, array $data, string $expected): void
    {
        $this->property->init(PropertyConfiguration::create($params));

        $this->assertEquals($expected, $this->property->getRawValue(new ResultRecord($data)));
    }

    public function valueDataProvider(): array
    {
        return [
            [
                'params' => [],
                'data' => [],
                'expected' => 'translated'
            ],
            [
                'params' => [
                    TranslatableProperty::DATA_NAME_KEY => 'test',
                ],
                'data' => [],
                'expected' => 'translated'
            ],
            [
                'params' => [
                    TranslatableProperty::DATA_NAME_KEY => 'test',
                ],
                'data' => [
                    'test' => 'value'
                ],
                'expected' => 'translated value'
            ],
            [
                'params' => [
                    TranslatableProperty::NAME_KEY => 'test',
                ],
                'data' => [],
                'expected' => 'translated'
            ],
            [
                'params' => [
                    TranslatableProperty::NAME_KEY => 'test',
                ],
                'data' => [
                    'test' => 'value'
                ],
                'expected' => 'translated value'
            ],
            [
                'params' => [
                    TranslatableProperty::TRANS_KEY => 'key',
                ],
                'data' => [],
                'expected' => 'translated key'
            ],
            [
                'params' => [
                    TranslatableProperty::TRANS_KEY => 'key',
                ],
                'data' => [
                    'key' => 'value'
                ],
                'expected' => 'translated key'
            ],
            [
                'params' => [
                    TranslatableProperty::TRANS_KEY => 'key',
                    TranslatableProperty::PARAMS_KEY => ['param1', 'param2' => 'value2'],
                    TranslatableProperty::DIRECT_PARAMS_KEY => ['param3' => 'value3'],
                ],
                'data' => [
                    'key' => 'value'
                ],
                'expected' => 'translated key %param1%=, %param2%=, %param3%=value3'
            ],
            [
                'params' => [
                    TranslatableProperty::TRANS_KEY => 'key',
                    TranslatableProperty::PARAMS_KEY => ['param1', 'param2' => 'value2'],
                    TranslatableProperty::DIRECT_PARAMS_KEY => ['param3' => 'value3'],
                ],
                'data' => [
                    'key' => 'value',
                    'param1' => 'data1',
                    'value2' => 'data2',
                ],
                'expected' => 'translated key %param1%=data1, %param2%=data2, %param3%=value3'
            ],
        ];
    }
}
