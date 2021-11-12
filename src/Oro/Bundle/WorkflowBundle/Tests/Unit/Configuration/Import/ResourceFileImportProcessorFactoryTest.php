<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\Import\ResourceFileImportProcessorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Config\FileLocatorInterface;

class ResourceFileImportProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var ResourceFileImportProcessorFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $fileLocator = $this->createMock(FileLocatorInterface::class);

        $this->factory = new ResourceFileImportProcessorFactory($this->reader, $fileLocator);
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array|string $import, bool $expected)
    {
        $this->assertEquals($expected, $this->factory->isApplicable($import));
    }

    public function isApplicableDataProvider(): \Generator
    {
        /**
         * ```
         * imports:
         *     - './stringFileName'
         * ```
         */
        yield 'simple inline' => [
            'import' => './stringFileName',
            true
        ];

        /**
         * ```
         * imports:
         *     - ['./stringFileName']
         * ```
         */
        yield 'simple array' => [
            'import' => ['./stringFileName'],
            true
        ];

        /**
         * ```
         * imports:
         *     - { resource: './stringFileName' }
         * ```
         */
        yield 'usual object' => [
            'import' => ['resource' => './stringFileName'],
            true
        ];

        /**
         * ```
         * imports:
         *     - { resource: './stringFileName', ignore_errors: true }
         * ```
         */
        yield 'usual object with ignore_errors' => [
            'import' => ['resource' => './stringFileName', 'ignore_errors' => true],
            true
        ];

        yield 'too big - not applicable' => [
            'import' => ['a', 'b', 'c'],
            false
        ];

        yield 'unknown option - not applicable' => [
            'import' => ['everything' => 42],
            false
        ];
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(array|string $import, string $expectedPath)
    {
        $processor = $this->factory->create($import);

        $this->assertEquals($expectedPath, ReflectionUtil::getPropertyValue($processor, 'importResource'));
        $this->assertSame($this->reader, ReflectionUtil::getPropertyValue($processor, 'reader'));
    }

    public function createDataProvider(): array
    {
        return [
            'string' => [
                'import' => './stringFileName',
                'expected' => './stringFileName'
            ],
            'array' => [
                'import' => ['./stringFileName'],
                'expected' => './stringFileName'
            ],
            'hash' => [
                'import' => ['resource' => './stringFileName'],
                'expected' => './stringFileName'
            ]
        ];
    }

    public function testCreateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Import options is not applicable for factory.');
        $this->factory->create(['*' => 42]);
    }
}
