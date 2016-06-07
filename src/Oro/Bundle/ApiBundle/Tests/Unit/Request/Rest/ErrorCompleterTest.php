<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\Rest\ErrorCompleter;

class ErrorCompleterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $exceptionTextExtractor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadata;

    /** @var ErrorCompleter */
    protected $errorCompleter;

    protected function setUp()
    {
        $this->exceptionTextExtractor = $this->getMock('Oro\Bundle\ApiBundle\Request\ExceptionTextExtractorInterface');

        $this->metadata = $this->getMockBuilder('Oro\Bundle\ApiBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->errorCompleter = new ErrorCompleter($this->exceptionTextExtractor);
    }

    public function testCompleteErrorWithoutInnerException()
    {
        $error = new Error();
        $expectedError = new Error();

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndAlreadyCompletedProperties()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setStatusCode(400);
        $error->setCode('test code');
        $error->setTitle('test title');
        $error->setDetail('test detail');
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerExceptionAndExceptionTextExtractorReturnsNothing()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setInnerException($exception);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorWithInnerException()
    {
        $exception = new \Exception('some exception');

        $error = new Error();
        $error->setInnerException($exception);

        $expectedError = new Error();
        $expectedError->setStatusCode(500);
        $expectedError->setCode('test code');
        $expectedError->setTitle('test title');
        $expectedError->setDetail('test detail');
        $expectedError->setInnerException($exception);

        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionStatusCode')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getStatusCode());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionCode')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getCode());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionType')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getTitle());
        $this->exceptionTextExtractor->expects($this->once())
            ->method('getExceptionText')
            ->with($this->identicalTo($exception))
            ->willReturn($expectedError->getDetail());

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(400);

        $expectedError = new Error();
        $expectedError->setStatusCode(400);
        $expectedError->setTitle(Response::$statusTexts[400]);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }

    public function testCompleteErrorTitleByUnknownStatusCode()
    {
        $error = new Error();
        $error->setStatusCode(1000);

        $expectedError = new Error();
        $expectedError->setStatusCode(1000);

        $this->errorCompleter->complete($error);
        $this->assertEquals($expectedError, $error);
    }
}
