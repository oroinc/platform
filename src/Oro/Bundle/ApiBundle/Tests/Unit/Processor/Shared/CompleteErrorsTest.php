<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\CompleteErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorTestCase;

class CompleteErrorsTest extends GetProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $errorCompleter;

    /** @var CompleteErrors */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->errorCompleter = $this->createMock('Oro\Bundle\ApiBundle\Request\ErrorCompleterInterface');

        $this->processor = new CompleteErrors($this->errorCompleter);
    }

    public function testProcessWithoutErrors()
    {
        $metadata = new EntityMetadata();

        $this->errorCompleter->expects($this->never())
            ->method('complete');

        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $metadata = new EntityMetadata();

        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), $this->identicalTo($metadata))
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenNoEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), null)
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenEntityTypeWasNotConvertedToEntityClass()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), null)
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->context->setClassName('test');
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadConfigFailed()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->willThrowException(new \Exception('load config exception'));
        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), null)
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }

    public function testProcessWhenLoadMetadataFailed()
    {
        $error = Error::createByException(new \Exception('some exception'));

        $this->metadataProvider->expects($this->once())
            ->method('getMetadata')
            ->willThrowException(new \Exception('load metadata exception'));
        $this->errorCompleter->expects($this->once())
            ->method('complete')
            ->with($this->identicalTo($error), null)
            ->willReturnCallback(
                function (Error $error) {
                    $error->setDetail($error->getInnerException()->getMessage());
                }
            );

        $this->context->addError($error);
        $this->context->setClassName('Test\Entity');
        $this->context->setConfig(new EntityDefinitionConfig());
        $this->processor->process($this->context);

        $expectedError = Error::createByException(new \Exception('some exception'))
            ->setDetail('some exception');

        $this->assertEquals([$expectedError], $this->context->getErrors());
    }
}
