<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Configurator;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachmentFilterConfigurationTest extends TestCase
{
    private const string FILTER_NAME = 'filter_name';

    private FilterConfiguration&MockObject $filterConfiguration;
    private AttachmentPostProcessorsProvider&MockObject $attachmentPostProcessorsProvider;
    private AttachmentFilterConfiguration $attachmentFilterConfiguration;

    #[\Override]
    protected function setUp(): void
    {
        $this->filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->attachmentPostProcessorsProvider = $this->createMock(AttachmentPostProcessorsProvider::class);
        $this->attachmentFilterConfiguration = new AttachmentFilterConfiguration(
            $this->filterConfiguration,
            $this->attachmentPostProcessorsProvider
        );
    }

    /**
     * @dataProvider filterProvider
     */
    public function testGet(array $actual, array $expected): void
    {
        $this->filterConfiguration->expects($this->once())
            ->method('get')
            ->with(self::FILTER_NAME)
            ->willReturn($actual);

        $this->attachmentPostProcessorsProvider->expects($this->any())
            ->method('getFilterConfig')
            ->willReturn($expected['post_processors']);

        $this->assertEquals($expected, $this->attachmentFilterConfiguration->get(self::FILTER_NAME));
    }

    public function testSet(): void
    {
        $filter = [
            'filter_option' => ['option'],
            'post_processors' => [
                'processor1' => ['processor_option1' => 'option1'],
                'processor2' => ['processor_option2' => 'option2']
            ]
        ];
        $this->filterConfiguration->expects($this->once())
            ->method('set')
            ->with(self::FILTER_NAME, $filter);

        $this->attachmentFilterConfiguration->set(self::FILTER_NAME, $filter);
    }

    /**
     * @dataProvider filterProvider
     */
    public function testAll(array $actual, array $expected): void
    {
        $this->filterConfiguration->expects($this->once())
            ->method('all')
            ->willReturn([$actual]);

        $this->attachmentPostProcessorsProvider->expects($this->any())
            ->method('getFilterConfig')
            ->willReturn($expected['post_processors']);

        $this->assertEquals([$expected], $this->attachmentFilterConfiguration->all());
    }

    public function filterProvider(): array
    {
        return [
            'With post processors' => [
                'actual' => [
                    'filter_option' => ['option'],
                    'post_processors' => [
                        'processor1' => ['processor_option' => 'option']
                    ],
                ],
                'expected' => [
                    'filter_option' => ['option'],
                    'post_processors' => [
                        'processor1' => ['processor_option' => 'option']
                    ]
                ]
            ],
            'With empty post processors' => [
                'actual' => [
                    'filter_option' => ['option'],
                    'post_processors' => []
                ],
                'expected' => [
                    'filter_option' => ['option'],
                    'post_processors' => [
                        'processor1' => ['processor_option1' => 'option1'],
                        'processor2' => ['processor_option2' => 'option2']
                    ]
                ]
            ],
            'Without post processors' => [
                'actual' => [
                    'filter_option' => ['option'],
                ],
                'expected' => [
                    'filter_option' => ['option'],
                    'post_processors' => [
                        'processor1' => ['processor_option1' => 'option1'],
                        'processor2' => ['processor_option2' => 'option2']
                    ]
                ]
            ]
        ];
    }
}
