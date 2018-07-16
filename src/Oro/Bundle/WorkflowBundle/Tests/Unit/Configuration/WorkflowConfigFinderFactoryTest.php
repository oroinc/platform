<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration;

use Oro\Bundle\WorkflowBundle\Configuration\ConfigFinderFactory;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfigFinderBuilder;
use Symfony\Component\Finder\Finder;

class WorkflowConfigFinderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigFinderFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $finderFactory;

    /** @var WorkflowConfigFinderBuilder */
    private $workflowConfigFinderBuilder;

    protected function setUp()
    {
        /** @var ConfigFinderFactory|\PHPUnit\Framework\MockObject\MockObject $finderFactory */
        $this->finderFactory = $this->createMock(ConfigFinderFactory::class);

        $this->workflowConfigFinderBuilder = new WorkflowConfigFinderBuilder($this->finderFactory);
    }

    public function testExceptionOnNotConfiguredSubDirectory()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Can not create finder. Not properly configured. No subDirectory specified.'
        );

        $this->workflowConfigFinderBuilder->create();
    }

    public function testExceptionOnNotConfiguredConfigFileName()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage(
            'Can not create finder. Not properly configured. No fileName specified.'
        );

        $this->workflowConfigFinderBuilder->setSubDirectory('subdir');

        $this->workflowConfigFinderBuilder->create();
    }

    public function testConfiguredPropertiesPassToFactory()
    {
        $finder1 = $this->createMock(Finder::class);
        $finder2 = $this->createMock(Finder::class);

        $this->finderFactory->expects($this->exactly(2))->method('create')
            ->withConsecutive(['subDir1', 'fileName1'], ['subDir2', 'fileName2'])
            ->willReturnOnConsecutiveCalls($finder1, $finder2);

        $this->workflowConfigFinderBuilder->setSubDirectory('subDir1');
        $this->workflowConfigFinderBuilder->setFileName('fileName1');
        $this->assertSame($finder1, $this->workflowConfigFinderBuilder->create());

        $this->workflowConfigFinderBuilder->setSubDirectory('subDir2');
        $this->workflowConfigFinderBuilder->setFileName('fileName2');

        $this->assertSame($finder2, $this->workflowConfigFinderBuilder->create());
    }
}
