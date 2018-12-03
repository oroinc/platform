<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\Import\ResourceFileImportProcessorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

class ResourceFileImportProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var ResourceFileImportProcessorFactory */
    private $factory;

    /** @var array */
    private $kernelBundles = ['TestBundle1', 'TestBundle2'];

    protected function setUp()
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $this->factory = new ResourceFileImportProcessorFactory($this->reader, $this->kernelBundles);
    }

    /**
     * @dataProvider isApplicableTest
     *
     * @param array|string $import
     * @param bool $expected
     */
    public function testIsApplicable($import, bool $expected)
    {
        $this->assertEquals($expected, $this->factory->isApplicable($import));
    }

    /**
     * @return \Generator
     */
    public function isApplicableTest()
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
     * @dataProvider createTestProvider
     *
     * @param array|string $import
     * @param string $expectedPath
     */
    public function testCreate($import, string $expectedPath)
    {
        $processor = $this->factory->create($import);

        $this->assertEquals($expectedPath, $this->getPrivateProperty($processor, 'importResource'));

        $this->assertSame($this->reader, $this->getPrivateProperty($processor, 'reader'));
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getPrivateProperty($object, string $property)
    {
        $get = \Closure::bind(
            function ($property) {
                return $this->{$property};
            },
            $object,
            $object
        );

        return $get($property);
    }

    /**
     * @return \Generator
     */
    public function createTestProvider()
    {
        yield 'string' => [
            'import' => './stringFileName',
            'expected' => './stringFileName'
        ];

        yield 'array' => [
            'import' => ['./stringFileName'],
            'expected' => './stringFileName'
        ];

        yield 'hash' => [
            'import' => ['resource' => './stringFileName'],
            'expected' => './stringFileName'
        ];
    }

    public function testCreateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Import options is not applicable for factory.');
        $this->factory->create(['*' => 42]);
    }
}
