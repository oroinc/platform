<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\SimpleProcessorFactory;
use Oro\Bundle\ApiBundle\Exception\NotSupportedConfigOperationException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Request\RequestType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NormalizeResultActionProcessorTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ACTION = 'test';

    /** @var SimpleProcessorFactory */
    protected $processorFactory;

    /** @var ProcessorBagConfigBuilder */
    protected $processorBagConfigBuilder;

    /** @var ProcessorBag */
    protected $processorBag;

    /** @var NormalizeResultActionProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->processorFactory = new SimpleProcessorFactory();
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBag = new ProcessorBag($this->processorBagConfigBuilder, $this->processorFactory);
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup(
            NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP,
            self::TEST_ACTION,
            -3
        );

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new NormalizeResultActionProcessor(
            $this->processorBag,
            self::TEST_ACTION
        );
    }

    /**
     * @return NormalizeResultContext
     */
    protected function getContext()
    {
        $context = new NormalizeResultContext();
        $context->setAction(self::TEST_ACTION);
        $context->getRequestType()->add(RequestType::REST);
        $context->getRequestType()->add(RequestType::JSON_API);
        $context->setVersion('1.2');

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
        $processor = $this->createMock('Oro\Component\ChainProcessor\ProcessorInterface');
        $this->processorFactory->addProcessor($processorId, $processor);
        $this->processorBagConfigBuilder->addProcessor(
            $processorId,
            [],
            self::TEST_ACTION,
            $groupName
        );

        return $processor;
    }

    public function loggerProvider()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testWhenNoProcessors()
    {
        $context = $this->getContext();
        $this->processor->process($context);
    }

    public function testWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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

        if (null !== $logger) {
            $logger->expects($this->once())
                ->method('error')
                ->with(
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->once())
                ->method('warning')
                ->with(
                    'An exception occurred in "processor1" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
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

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessor($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor2->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
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

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor2->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        if (null !== $logger) {
            $logger->expects($this->once())
                ->method('error')
                ->with(
                    'The execution of "processor1" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndNormalizeResultGroupIsDisabledAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->once())
                ->method('warning')
                ->with(
                    'An exception occurred in "processor1" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        $this->expectException('\Oro\Bundle\ApiBundle\Exception\RuntimeException');
        $this->expectExceptionMessage(sprintf('An unexpected error occurred: %s.', $error->getTitle()));

        if (null !== $logger) {
            $logger->expects($this->once())
                ->method('error')
                ->with('The execution of "processor1" processor is failed.');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndNormalizeResultGroupIsDisabledAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);
        // disable "normalize_result" group
        $context->setLastGroup('group2');

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->never())
            ->method('process');

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects($this->never())
            ->method('process');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        if (null !== $logger) {
            $logger->expects($this->once())
                ->method('error')
                ->with(
                    'The execution of "processor10" processor is failed.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects($this->never())
            ->method('process');

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->once())
                ->method('warning')
                ->with(
                    'An exception occurred in "processor10" processor.',
                    [
                        'exception'   => $exception,
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroup($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
        $processor11 = $this->addProcessor('processor11', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->willReturnCallback(
                function (NormalizeResultContext $context) use ($error) {
                    $context->addError($error);
                }
            );
        $processor11->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        if (null !== $logger) {
            $logger->expects($this->never())
                ->method('error');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenInternalPhpErrorOccurs($withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock('Psr\Log\LoggerInterface');
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $internalPhpError = new \Error('test error', 1);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

        $processor1->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context))
            ->will(new \PHPUnit_Framework_MockObject_Stub_Exception($internalPhpError));
        $processor2->expects($this->never())
            ->method('process');
        $processor3->expects($this->never())
            ->method('process');
        $processor10->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($context));

        if (null !== $logger) {
            $logger->expects($this->once())
                ->method('error')
                ->with('The execution of "processor1" processor is failed.');
            $logger->expects($this->never())
                ->method('warning');
        }

        $this->processor->process($context);

        $errors = $context->getErrors();
        $this->assertCount(1, $errors);
        $errorException = $errors[0]->getInnerException();
        $this->assertInstanceOf(\ErrorException::class, $errorException);
        $this->assertSame($internalPhpError->getMessage(), $errorException->getMessage());
        $this->assertSame($internalPhpError->getCode(), $errorException->getCode());
        $this->assertSame($internalPhpError->getFile(), $errorException->getFile());
        $this->assertSame($internalPhpError->getLine(), $errorException->getLine());
    }

    public function testWhenValidationExceptionOccurs()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');
        $this->processor->setLogger($logger);

        $context = $this->getContext();

        $exception = new NotSupportedConfigOperationException('Test\Class', 'test_operation');

        $error = Error::createByException($exception);

        $processor1 = $this->addProcessor('processor1', 'group1');
        $processor2 = $this->addProcessor('processor2', 'group1');
        $processor3 = $this->addProcessor('processor3', 'group2');
        $processor10 = $this->addProcessor('processor10', NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);

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

        $logger->expects($this->never())
            ->method('error');
        $logger->expects($this->never())
            ->method('warning');

        $this->processor->process($context);

        $this->assertEquals(
            [$error],
            $context->getErrors()
        );
    }
}
