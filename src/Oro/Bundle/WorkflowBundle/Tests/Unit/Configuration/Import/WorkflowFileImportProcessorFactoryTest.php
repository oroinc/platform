<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowFileImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowFileImportProcessorFactory;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;

class WorkflowFileImportProcessorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowFileImportProcessorFactory */
    private $factory;

    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    protected function setUp()
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $this->factory = new WorkflowFileImportProcessorFactory($this->reader);
    }

    /**
     * @dataProvider applicableTestCases
     * @param mixed $import
     * @param bool $expected
     */
    public function testApplicable($import, bool $expected)
    {
        $this->assertEquals($expected, $this->factory->isApplicable($import));
    }

    /**
     * @return \Generator
     */
    public function applicableTestCases()
    {
        yield 'correct' => [
            'import' => [
                'resource' => './file',
                'workflow' => 'name1',
                'as' => 'name2',
                'replace' => ['node']
            ],
            true
        ];

        yield 'incorrect 1' => [
            'import' => [
                'resource' => './file'
            ],
            false
        ];

        yield 'incorrect 2' => [
            'import' => './file1',
            false
        ];

        yield 'incorrect 3' => [
            'import' => [
                'workflow' => 'name',
                'as' => 'name',
                'replace' => ['node']
            ],
            false
        ];
    }

    public function testCreate()
    {
        $resource = './file';
        $target = 'target';
        $workflowForImport = 'resource';
        $replace = ['node1', 'node2'];

        $import = [
            'workflow' => $workflowForImport,
            'as' => $target,
            'resource' => $resource,
            'replace' => $replace
        ];

        $instance = $this->factory->create($import);

        $this->assertInstanceOf(WorkflowFileImportProcessor::class, $instance);

        $this->assertSame($resource, $this->getPrivateProperty($instance, 'file'));
        $this->assertSame($workflowForImport, $this->getPrivateProperty($instance, 'resource'));
        $this->assertSame($target, $this->getPrivateProperty($instance, 'target'));
        $this->assertSame($replace, $this->getPrivateProperty($instance, 'replacements'));
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed
     */
    protected function getPrivateProperty($object, string $property)
    {
        return \Closure::bind(
            function ($property) {
                return $this->{$property};
            },
            $object,
            $object
        )($property);
    }
}
