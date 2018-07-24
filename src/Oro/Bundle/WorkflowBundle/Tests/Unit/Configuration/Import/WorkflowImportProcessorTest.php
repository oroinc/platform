<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Configuration\ConfigImportProcessorInterface;
use Oro\Bundle\WorkflowBundle\Configuration\Import\WorkflowImportProcessor;
use Oro\Bundle\WorkflowBundle\Configuration\Reader\ConfigFileReaderInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowConfigurationImportException;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Import\Stub\StubWorkflowImportCallbackProcessor;
use Symfony\Component\Finder\Finder;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WorkflowImportProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowConfigFinderBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $finderBuilder;

    /** @var ConfigFileReaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $reader;

    /** @var WorkflowImportProcessor */
    private $processor;

    protected function setUp()
    {
        $this->reader = $this->createMock(ConfigFileReaderInterface::class);
        $this->finderBuilder = $this->createMock(WorkflowConfigFinderBuilder::class);

        $this->processor = new WorkflowImportProcessor($this->reader, $this->finderBuilder);
    }

    /**
     * @param string $resource
     * @param string $target
     * @param array $replacements
     */
    private function configureProcessor(string $resource, string $target, array $replacements = [])
    {
        $this->processor->setResource($resource);
        $this->processor->setTarget($target);
        $this->processor->setReplacements($replacements);
    }

    public function testProcessImportWithinSameContent()
    {
        $content = [
            'workflows' => [
                'one' => [
                    'node_to_replace_from_one' => '*',
                    'steps' => [
                        'step_a' => null,
                        'step_b' => [
                            'is_start' => true
                        ]
                    ]
                ],
                'two' => [
                    'steps' => [
                        'step_c' => [
                            'is_start' => true
                        ]
                    ]
                ]
            ]
        ];

        $this->configureProcessor('one', 'two', ['node_to_replace_from_one', 'steps.step_b']);

        $resultContent = $this->processor->process($content, new \SplFileInfo(__FILE__));

        $this->assertEquals(
            [
                'workflows' => [
                    'one' => [
                        'node_to_replace_from_one' => '*',
                        'steps' => [
                            'step_a' => null,
                            'step_b' => [
                                'is_start' => true
                            ]
                        ]
                    ],
                    'two' => [
                        'steps' => [
                            'step_c' => [
                                'is_start' => true
                            ],
                            'step_a' => null,
                        ]
                    ]
                ]
            ],
            $resultContent
        );
    }

    public function testParentChangesAccepted()
    {
        $content = [
            'workflows' => [
                'one' => [
                    'steps' => [
                        'step_a' => null,
                        'step_b' => [
                            'is_start' => true
                        ]
                    ]
                ],
                'two' => [
                    'steps' => [
                        'step_c' => [
                            'is_start' => true
                        ]
                    ]
                ]
            ]
        ];

        $changedByParent = [
            'workflows' => [
                'one' => [
                    'steps' => ['step_c' => null, 'step_z' => null]
                ],
                'two' => [
                    'steps' => ['step_c' => ['is_start' => true], 'step_z' => null]
                ]
            ]
        ];

        $expectedResult = [
            'workflows' => [
                'one' => [
                    'steps' => [
                        'step_c' => null, //this would be replaced by target's one node content
                        'step_z' => null
                    ]
                ],
                'two' => [
                    'steps' => [
                        'step_c' => [
                            'is_start' => true
                        ],
                        'step_z' => null,
                    ]
                ]
            ]
        ];

        $file = new \SplFileInfo(__FILE__);

        $this->configureProcessor('one', 'two', ['steps.step_b']);

        /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $parent */
        $parent = $this->createMock(ConfigImportProcessorInterface::class);
        $this->processor->setParent($parent);

        $parent->expects($this->once())->method('process')
            ->with($content, $file)
            ->willReturn($changedByParent);

        $resultContent = $this->processor->process($content, $file);

        $this->assertEquals(
            $expectedResult,
            $resultContent
        );
    }

    public function testProcessOuterSearch()
    {
        $this->configureProcessor('workflow_to_import', 'one', ['steps']);

        $currentContext = [
            'workflows' => [
                'one' => [
                    'steps' => [
                        'step_one' => ['is_start' => true]
                    ]
                ]
            ]
        ];

        $file1Content = ['workflows' => ['not_ours' => ['...']]];

        $file2Content = [
            'workflows' => [
                'not_to_import' => ['entity' => 'Entity2'],
                'workflow_to_import' => ['entity' => 'Entity1', 'steps' => ['will be replaced']],
            ]
        ];

        $result = [
            'workflows' => [
                'one' => [
                    'entity' => 'Entity1',
                    'steps' => ['step_one' => ['is_start' => true]]
                ]
            ]
        ];

        /** @var ConfigImportProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $parent */
        $parent = $this->createMock(ConfigImportProcessorInterface::class);

        /** @var Finder|\PHPUnit\Framework\MockObject\MockObject $finderMock */
        $finderMock = $this->createMock(Finder::class);

        $currentFile = new \SplFileInfo(__FILE__);
        $file1 = new \SplFileInfo('file1');
        $file2 = new \SplFileInfo('file2');
        $filesLookingTo = new ArrayCollection([$file1, $file2]);

        $parent->expects($this->exactly(3))->method('process')
            ->withConsecutive([$currentContext, $currentFile], [$file1Content, $file1], [$file2Content, $file2])
            ->willReturnOnConsecutiveCalls($currentContext, $file1Content, $file2Content);

        $this->finderBuilder->expects($this->once())->method('create')
            ->willReturn($finderMock);

        $callbackConstraint = $this->callbackShouldFilterCurrentFile(
            $filesLookingTo,
            new ArrayCollection([$currentFile, $file1, $file2])
        );

        $finderMock->expects($this->once())->method('filter')
            ->with($callbackConstraint)->willReturn($filesLookingTo);

        $finderMock->expects($this->once())->method('getIterator')->willReturn($filesLookingTo);

        $this->reader->expects($this->exactly(2))->method('read')
            ->withConsecutive([$file1], [$file2])
            ->willReturnOnConsecutiveCalls($file1Content, $file2Content);

        $this->processor->setParent($parent);
        $processed = $this->processor->process($currentContext, $currentFile);

        $this->assertEquals($result, $processed);
    }

    /**
     * @param ArrayCollection $expected
     * @param ArrayCollection $files
     * @return \PHPUnit\Framework\Constraint\Callback
     */
    protected function callbackShouldFilterCurrentFile(
        ArrayCollection $expected,
        ArrayCollection $files
    ): \PHPUnit\Framework\Constraint\Callback {
        //callback constraint for argument
        return $this->callback(function (callable $filter) use ($files, $expected) {
            $this->assertEquals(
                $expected->getValues(),
                $files->filter($filter)->getValues()
            );

            return true;
        });
    }

    public function testImplementsWorkflowImportTrait()
    {
        $accessors = [
            [
                'resource',
                'name of resource'
            ],
            [
                'target',
                'name of target'
            ],
            [
                'replacements',
                ['array', 'of', 'replacements']
            ]
        ];

        foreach ($accessors as list($name, $value)) {
            $setter = 'set' . ucfirst($name);
            $this->processor->{$setter}($value);
            $getter = 'get' . ucfirst($name);
            $this->assertSame($value, $this->processor->{$getter}());
        }
    }

    /**
     * @dataProvider propertiesToConfigure
     * @param string $property
     * @param string $type
     */
    public function testMustBeConfiguredBeforeUsage(string $property, string $type)
    {
        $getter = 'get' . ucfirst($property);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(
            sprintf(
                'Return value of %s::%s() must be of the type %s, null returned',
                WorkflowImportProcessor::class,
                $getter,
                $type
            )
        );

        $this->processor->{$getter}();
    }

    /**
     * @return \Generator
     */
    public function propertiesToConfigure()
    {
        yield ['target', 'string'];
        yield ['resource', 'string'];
        yield ['replacements', 'array'];
    }

    public function testExceptionWorkflowForImportNotFound()
    {
        $this->configureProcessor('workflow_to_import', 'one', ['steps']);

        $currentContext = [
            'workflows' => [
                'one' => [
                    'steps' => [
                        'step_one' => ['is_start' => true]
                    ]
                ]
            ]
        ];

        $file1Content = ['workflows' => ['not_ours' => ['...']]];

        $file2Content = [
            'workflows' => [
                'not_to_import' => ['entity' => 'Entity2'],
                'not_to_import_also' => ['entity' => 'Entity1', 'steps' => ['will be replaced']],
            ]
        ];

        /** @var Finder|\PHPUnit\Framework\MockObject\MockObject $finderMock */
        $finderMock = $this->createMock(Finder::class);

        $currentFile = new \SplFileInfo(__FILE__);
        $file1 = new \SplFileInfo('file1');
        $file2 = new \SplFileInfo('file2');
        $filesLookingTo = new ArrayCollection([$file1, $file2]);

        $this->finderBuilder->expects($this->once())->method('create')
            ->willReturn($finderMock);

        $callbackConstraint = $this->callbackShouldFilterCurrentFile(
            $filesLookingTo,
            new ArrayCollection([$currentFile, $file1, $file2])
        );

        $finderMock->expects($this->once())->method('filter')
            ->with($callbackConstraint)->willReturn($filesLookingTo);

        $finderMock->expects($this->once())->method('getIterator')->willReturn($filesLookingTo);

        $this->reader->expects($this->exactly(2))->method('read')
            ->withConsecutive([$file1], [$file2])
            ->willReturnOnConsecutiveCalls($file1Content, $file2Content);

        $this->expectException(WorkflowConfigurationImportException::class);
        $this->expectExceptionMessage('Can not find workflow `workflow_to_import` for import.');

        $this->processor->process($currentContext, $currentFile);
    }

    public function testInProgress()
    {
        $stubCbParentProcessor = new StubWorkflowImportCallbackProcessor(function (array $content) {
            $this->assertTrue($this->processor->inProgress());

            return $content;
        });

        $this->processor->setParent($stubCbParentProcessor);

        $content = [
            'workflows' => [
                'workflow_to_import' => ['*' => [42]],
                'one' => ['*' => ['everything']]
            ]
        ];

        $result = [
            'workflows' => [
                'workflow_to_import' => ['*' => [42]],
                'one' => ['*' => [42, 'everything']]
            ]
        ];
        $this->configureProcessor('workflow_to_import', 'one', ['steps']);
        $processed = $this->processor->process($content, new \SplFileInfo(__FILE__));
        $this->assertEquals($result, $processed);
    }
}
