<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\SimpleProcessorFactory;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;

class RequestActionProcessorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ACTION = 'test';

    /** @var SimpleProcessorFactory */
    protected $processorFactory;

    /** @var ProcessorBag */
    protected $processorBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataProvider;

    /** @var RequestActionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processorFactory = new SimpleProcessorFactory();
        $this->processorBag = new ProcessorBag($this->processorFactory);
        $this->processorBag->addGroup('group1', self::TEST_ACTION);
        $this->processorBag->addGroup('group2', self::TEST_ACTION);
        $this->processorBag->addGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP, self::TEST_ACTION);

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new RequestActionProcessor(
            $this->processorBag,
            self::TEST_ACTION,
            $this->configProvider,
            $this->metadataProvider
        );
    }

    /**
     * @return Context
     */
    protected function getContext()
    {
        $context = new Context($this->configProvider, $this->metadataProvider);
        $context->setAction(self::TEST_ACTION);

        return $context;
    }

    /**
     * @param string $processorId
     * @param string $groupName
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function addProcessor($processorId, $groupName)
    {
        $processor = $this->getMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $this->processorFactory->addProcessor($processorId, $processor);
        $this->processorBag->addProcessor(
            $processorId,
            [],
            self::TEST_ACTION,
            $groupName
        );

        return $processor;
    }

    public function testProcessEmptyProcessors()
    {
        $context = $this->getContext();
        $this->processor->process($context);
    }

    public function testProcessWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $this->processor->process($context);
    }

    public function testProcessWhenExceptionOccurs()
    {
        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    public function testProcessWhenExceptionOccursAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $logger->expects($this->once())
            ->method('error')
            ->with('The execution of "processor1" processor is failed.', ['exception' => $exception]);

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    public function testProcessWhenErrorOccurs()
    {
        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    public function testProcessWhenErrorOccursAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $logger->expects($this->never())
            ->method('error');

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    public function testProcessWhenExceptionOccursAndNormalizeResultGroupIsDisabled()
    {
        $context = $this->getContext();
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        $this->setExpectedException(
            get_class($exception),
            $exception->getMessage()
        );

        $this->processor->process($context);
    }

    public function testProcessWhenExceptionOccursAndNormalizeResultGroupIsDisabledAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        $this->setExpectedException(
            get_class($exception),
            $exception->getMessage()
        );

        $logger->expects($this->once())
            ->method('error')
            ->with('The execution of "processor1" processor is failed.', ['exception' => $exception]);

        $this->processor->process($context);
    }

    public function testProcessWhenErrorOccursAndNormalizeResultGroupIsDisabled()
    {
        $context = $this->getContext();
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        $this->setExpectedException(
            '\Oro\Bundle\ApiBundle\Exception\RuntimeException',
            sprintf('An unexpected error occurred: %s.', $error->getTitle())
        );

        $this->processor->process($context);
    }

    public function testProcessWhenErrorOccursAndNormalizeResultGroupIsDisabledAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        $this->setExpectedException(
            '\Oro\Bundle\ApiBundle\Exception\RuntimeException',
            sprintf('An unexpected error occurred: %s.', $error->getTitle())
        );

        $logger->expects($this->once())
            ->method('error')
            ->with('The execution of "processor1" processor is failed.');

        $this->processor->process($context);
    }

    public function testProcessWhenExceptionOccursInNormalizeResultGroup()
    {
        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects($this->never())
            ->method('process');

        $this->setExpectedException(get_class($exception), $exception->getMessage());

        $this->processor->process($context);
    }

    public function testProcessWhenExceptionOccursInNormalizeResultGroupAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects($this->never())
            ->method('process');

        $this->setExpectedException(get_class($exception), $exception->getMessage());

        $logger->expects($this->once())
            ->method('error')
            ->with('The execution of "processor10" processor is failed.', ['exception' => $exception]);

        $this->processor->process($context);
    }

    public function testProcessWhenErrorOccursInNormalizeResultGroup()
    {
        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    public function testProcessWhenErrorOccursInNormalizeResultGroupAndLoggerExists()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', RequestActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (Context $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $logger->expects($this->never())
            ->method('error');

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }
}
