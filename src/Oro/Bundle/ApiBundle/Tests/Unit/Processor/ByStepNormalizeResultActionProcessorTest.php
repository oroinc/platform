<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultContext;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ProcessorBag;
use Oro\Component\ChainProcessor\ProcessorBagConfigBuilder;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ProcessorRegistryInterface;
use Oro\Component\Testing\Logger\BufferingLogger;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ByStepNormalizeResultActionProcessorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ACTION = 'test';

    /** @var ProcessorRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var ProcessorBagConfigBuilder */
    private $processorBagConfigBuilder;

    /** @var ByStepNormalizeResultActionProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistryInterface::class);
        $this->processorBagConfigBuilder = new ProcessorBagConfigBuilder();
        $this->processorBagConfigBuilder->addGroup('group1', self::TEST_ACTION, -1);
        $this->processorBagConfigBuilder->addGroup('group2', self::TEST_ACTION, -2);
        $this->processorBagConfigBuilder->addGroup(ApiActionGroup::NORMALIZE_RESULT, self::TEST_ACTION, -3);

        $this->processor = new ByStepNormalizeResultActionProcessor(
            new ProcessorBag($this->processorBagConfigBuilder, $this->processorRegistry),
            self::TEST_ACTION
        );
    }

    private function getContext(): ByStepNormalizeResultContext
    {
        $context = new ByStepNormalizeResultContext();
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

    public function loggerProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    public function testBothFirstAndLastGroupsAreNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Both the first and the last groups must be specified for the "test" action and these groups must be equal.'
            . ' First Group: "". Last Group: "".'
        );

        $context = new ByStepNormalizeResultContext();
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

        $context = new ByStepNormalizeResultContext();
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

        $context = new ByStepNormalizeResultContext();
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

        $context = new ByStepNormalizeResultContext();
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
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertFalse($context->hasSkippedGroups());
            });

        $this->processor->process($context);
        self::assertFalse($context->hasSkippedGroups());
        self::assertFalse($context->hasErrors());
    }

    public function testShouldRemoveSourceGroupBeforeCallParentProcess()
    {
        $context = $this->getContext();
        $context->setSourceGroup('group2');

        [$processor1] = $this->addProcessors([
            'processor1' => 'group1'
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertNull($context->getSourceGroup());
            });

        $this->processor->process($context);
        self::assertSame('group1', $context->getSourceGroup());
        self::assertFalse($context->hasErrors());
    }

    public function testShouldRemoveFailedGroupBeforeCallParentProcess()
    {
        $context = $this->getContext();
        $context->setFailedGroup('group2');

        [$processor1] = $this->addProcessors([
            'processor1' => 'group1'
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertNull($context->getFailedGroup());
            });

        $this->processor->process($context);
        self::assertSame('', $context->getFailedGroup());
        self::assertFalse($context->hasErrors());
    }

    public function testWhenNoExceptionsAndErrors()
    {
        $context = $this->getContext();

        [$processor1, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertEquals('group1', $context->getSourceGroup());
                self::assertSame('', $context->getFailedGroup());
            });

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccurs(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::once())
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
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('info');
            $logger->expects(self::once())
                ->method('error')
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

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccurs(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessor(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [['title' => $error->getTitle()]],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    public function testWhenNoExceptionsAndErrorsAndHasInitialErrors()
    {
        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        [$processor1, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($initialError) {
                self::assertEquals([$initialError], $context->getErrors());
            });
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertEquals('group1', $context->getSourceGroup());
                self::assertSame('', $context->getFailedGroup());
            });

        $this->processor->process($context);

        self::assertEquals([$initialError], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::once())
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
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursAndSoftErrorsHandlingEnabledAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('info');
            $logger->expects(self::once())
                ->method('error')
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

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursAndSoftErrorsHandlingEnabledAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor1" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInLastProcessorAndSoftErrorsHandlingEnabledAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor2, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));
        $processor2->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::once())
                ->method('info')
                ->with(
                    'Error(s) occurred in "processor2" processor.',
                    [
                        'errors'      => [
                            ['title' => $initialError->getTitle()],
                            ['title' => $error->getTitle()]
                        ],
                        'action'      => self::TEST_ACTION,
                        'requestType' => 'rest,json_api',
                        'version'     => '1.2'
                    ]
                );
        }

        $this->processor->process($context);

        self::assertEquals([$initialError, $error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setLastGroup(ApiActionGroup::NORMALIZE_RESULT);

        $exception = new \Exception('test exception');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        if (null !== $logger) {
            $logger->expects(self::once())
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
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenExceptionOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setLastGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setSoftErrorsHandling(true);

        $exception = new \Exception('test exception');

        $error = Error::createByException($exception);

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($exception);
        $processor11->expects(self::never())
            ->method('process');

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('info');
            $logger->expects(self::once())
                ->method('error')
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

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroup(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setLastGroup(ApiActionGroup::NORMALIZE_RESULT);

        $error = Error::create('some error');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenErrorOccursInNormalizeResultGroupAndSoftErrorsHandlingEnabled(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = $this->createMock(LoggerInterface::class);
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $context->setFirstGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setLastGroup(ApiActionGroup::NORMALIZE_RESULT);
        $context->setSoftErrorsHandling(true);

        $error = Error::create('some error');

        [$processor1, $processor10, $processor11] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT,
            'processor11' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) use ($error) {
                $context->addError($error);
            });
        $processor11->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context));

        if (null !== $logger) {
            $logger->expects(self::never())
                ->method('error');
            $logger->expects(self::never())
                ->method('info');
        }

        $this->processor->process($context);

        self::assertEquals([$error], $context->getErrors());
    }

    /**
     * @dataProvider loggerProvider
     */
    public function testWhenInternalPhpErrorOccursAndHasInitialErrors(bool $withLogger)
    {
        $logger = null;
        if ($withLogger) {
            $logger = new BufferingLogger();
            $this->processor->setLogger($logger);
        }

        $context = $this->getContext();
        $initialError = Error::create('initial error');
        $context->addError($initialError);

        $internalPhpError = new \Error('test error', 1);

        [$processor1, $processor2, $processor3, $processor10] = $this->addProcessors([
            'processor1'  => 'group1',
            'processor2'  => 'group1',
            'processor3'  => 'group2',
            'processor10' => ApiActionGroup::NORMALIZE_RESULT
        ]);

        $processor1->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willThrowException($internalPhpError);
        $processor2->expects(self::never())
            ->method('process');
        $processor3->expects(self::never())
            ->method('process');
        $processor10->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($context))
            ->willReturnCallback(function (ByStepNormalizeResultContext $context) {
                self::assertSame('', $context->getSourceGroup());
                self::assertEquals('group1', $context->getFailedGroup());
            });

        $this->processor->process($context);

        $errors = $context->getErrors();
        self::assertCount(2, $errors);
        self::assertSame($initialError, $errors[0]);
        $errorException = $errors[1]->getInnerException();
        self::assertInstanceOf(\ErrorException::class, $errorException);
        self::assertSame($internalPhpError->getMessage(), $errorException->getMessage());
        self::assertSame($internalPhpError->getCode(), $errorException->getCode());
        self::assertSame($internalPhpError->getFile(), $errorException->getFile());
        self::assertSame($internalPhpError->getLine(), $errorException->getLine());

        if (null !== $logger) {
            $logs = $logger->cleanLogs();
            self::assertCount(1, $logs);
            unset($logs[0][2]);
            self::assertEquals(
                [
                    [
                        'error',
                        'The execution of "processor1" processor is failed.'
                    ]
                ],
                $logs
            );
        }
    }
}
