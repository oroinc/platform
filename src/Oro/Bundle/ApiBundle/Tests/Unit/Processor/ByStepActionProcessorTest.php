<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\ByStepActionProcessor;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;

class ByStepActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test';

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessorRegistryInterface */
    private $processorRegistry;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var ProcessorBag */
    private $processorBag;

    /** @var ByStepActionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->processorBagConfigBuilder, $this->processorRegistry);
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup('group3', self::TEST_ACTION, -3);

        $this->processor = new ByStepActionProcessor(
            $this->processorBag,
            self::TEST_ACTION
        );
    }

    private function getContext(): ApiContext
    {
        $context = new CustomizeLoadedDataContext();
        $context->setAction(self::TEST_ACTION);
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');
        $context->setFirstGroup('group1');
        $context->setLastGroup('group1');

        return $context;
    }

    /**
     * @param array $processors [processorId => groupName, ...]
     *
     * @return ProcessorInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function addProcessors(array $processors): array
    {
        $createdProcessors = [];
        $processorRegistryMap = [];
        foreach ($processors as $processorId => $groupName) {
            $this->processorBagConfigBuilder->addProcessor(
                $processorId,
                [],
                self::TEST_ACTION,
                $groupName
            );
            $processor = $this->createMock(ProcessorInterface::class);
            $createdProcessors[] = $processor;
            $processorRegistryMap[] = [$processorId, $processor];
        }
        $this->processorRegistry->expects(self::any())
            ->method('getProcessor')
            ->willReturnMap($processorRegistryMap);

        return $createdProcessors;
    }

    public function testBothFirstAndLastGroupsAreNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Both the first and the last groups must be specified for the "test" action and these groups must be equal.'
            . ' First Group: "". Last Group: "".'
        );

        $context = new CustomizeLoadedDataContext();
        $context->setAction(self::TEST_ACTION);
        $this->processor->process($context);
    }

    public function testFirstGroupIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Both the first and the last groups must be specified for the "test" action and these groups must be equal.'
            . ' First Group: "". Last Group: "group1".'
        );

        $context = new CustomizeLoadedDataContext();
        $context->setAction(self::TEST_ACTION);
        $context->setLastGroup('group1');
        $this->processor->process($context);
    }

    public function testLastGroupIsNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Both the first and the last groups must be specified for the "test" action and these groups must be equal.'
            . ' First Group: "group1". Last Group: "".'
        );

        $context = new CustomizeLoadedDataContext();
        $context->setAction(self::TEST_ACTION);
        $context->setFirstGroup('group1');
        $this->processor->process($context);
    }

    public function testFirstGroupIsNotEqualLastGroup()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Both the first and the last groups must be specified for the "test" action and these groups must be equal.'
            . ' First Group: "group1". Last Group: "group2".'
        );

        $context = new CustomizeLoadedDataContext();
        $context->setAction(self::TEST_ACTION);
        $context->setFirstGroup('group1');
        $context->setLastGroup('group2');
        $this->processor->process($context);
    }

    public function testWhenNoProcessors()
    {
        $context = $this->getContext();
        $this->processor->process($context);
    }

    public function testShouldRemoveSkippedGroupsBeforeCallParentProcess()
    {
        $context = $this->getContext();
        $context->skipGroup('group2');

        [$processor1] = $this->addProcessors([
            'processor1' => 'group1'
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ApiContext $context) {
                self::assertFalse($context->hasSkippedGroups());
            });

        $this->processor->process($context);
        self::assertFalse($context->hasSkippedGroups());
    }

    public function testWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();
        $context->setFirstGroup('group2');
        $context->setLastGroup('group2');

        [$processor1, $processor2, $processor3] = $this->addProcessors([
            'processor1' => 'group1',
            'processor2' => 'group2',
            'processor3' => 'group3'
        ]);

        $processor1->expects(self::never())
            ->method('process');
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor3->expects(self::never())
            ->method('process');

        $this->processor->process($context);
    }
}
