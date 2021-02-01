<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\CheckRequestType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckRequestTypeTest extends GetListProcessorTestCase
{
    /** @var CheckRequestType */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new CheckRequestType();
    }

    public function testProcessWhenRequestTypeAlreadyDetected(): void
    {
        $this->context->setProcessed(CheckRequestType::OPERATION_NAME);
        $this->context->getRequestHeaders()->set('Accept', 'application/vnd.api+json');
        $this->processor->process($this->context);
        self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testRequestTypeAlreadyContainJsonApiAspect(): void
    {
        $this->context->getRequestType()->add(RequestType::JSON_API);
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testNoAcceptAndContentType(): void
    {
        $this->processor->process($this->context);
        self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
        self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testNonJsonApiAccept(): void
    {
        $this->context->getRequestHeaders()->set('Accept', 'text/plain');
        $this->processor->process($this->context);
        self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
        self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiAcceptWithoutMediaTypeParameters(): void
    {
        $this->context->getRequestHeaders()->set('Accept', 'application/vnd.api+json');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiAcceptWithMediaTypeParameters(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage(
            'The "Accept" request header should contains at least one instance of JSON:API media type'
            . ' without any parameters.'
        );

        $this->context->getRequestHeaders()->set('Accept', 'application/vnd.api+json; charset=UTF-8');
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }

    public function testSeveralAcceptsIncludingJsonApiWithoutMediaTypeParameters(): void
    {
        $this->context->getRequestHeaders()->set(
            'Accept',
            ['text/plain; charset=UTF-8', 'application/vnd.api+json']
        );
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testSeveralAcceptsIncludingJsonApiWithMediaTypeParameters(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage(
            'The "Accept" request header should contains at least one instance of JSON:API media type'
            . ' without any parameters.'
        );

        $this->context->getRequestHeaders()->set(
            'Accept',
            ['text/plain; charset=UTF-8', 'application/vnd.api+json; charset=UTF-16']
        );
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }

    public function testSeveralJsonApiAcceptsButNoJsonApiMediaTypeWithoutParameters(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage(
            'The "Accept" request header should contains at least one instance of JSON:API media type'
            . ' without any parameters.'
        );

        $this->context->getRequestHeaders()->set(
            'Accept',
            ['application/vnd.api+json; charset=UTF-8', 'application/vnd.api+json; charset=UTF-16']
        );
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }

    public function testSeveralJsonApiAcceptsAndHasJsonApiMediaTypeWithoutParameters(): void
    {
        $this->context->getRequestHeaders()->set(
            'Accept',
            ['application/vnd.api+json; charset=UTF-8', 'application/vnd.api+json']
        );
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testNonJsonApiContentType(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'text/plain; charset=UTF-8');
        $this->processor->process($this->context);
        self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
        self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithoutMediaTypeParameters(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithMediaTypeParameters(): void
    {
        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->expectExceptionMessage(
            'The "Content-Type" request header should contain JSON:API media type without any parameters.'
        );

        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json; charset=UTF-8');
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }

    public function testJsonApiContentTypeAndJsonApiAcceptWithoutMediaTypeParameters(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', 'application/vnd.api+json');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithoutMediaTypeParametersAndAcceptWithMediaTypeParameters(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage(
            'The "Accept" request header should contains at least one instance of JSON:API media type'
            . ' without any parameters.'
        );

        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', 'application/vnd.api+json; charset=UTF-8');
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }

    public function testJsonApiContentTypeWithoutMediaTypeParametersAndJsonAccept(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', 'application/json');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithoutMediaTypeParametersAndAnyApplicationDocumentAccept(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', 'application/*');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithoutMediaTypeParametersAndAnyDocumentAccept(): void
    {
        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', '*/*');
        $this->processor->process($this->context);
        self::assertEquals(
            [RequestType::REST, RequestType::JSON_API],
            $this->context->getRequestType()->toArray()
        );
        self::assertTrue($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
    }

    public function testJsonApiContentTypeWithoutMediaTypeParametersAndNonJsonAccept(): void
    {
        $this->expectException(NotAcceptableHttpException::class);
        $this->expectExceptionMessage(
            'The "Accept" request header does not accept JSON:API content type.'
        );

        $this->context->getRequestHeaders()->set('Content-Type', 'application/vnd.api+json');
        $this->context->getRequestHeaders()->set('Accept', 'text/plain');
        try {
            $this->processor->process($this->context);
        } catch (\Throwable $e) {
            self::assertEquals([RequestType::REST], $this->context->getRequestType()->toArray());
            self::assertFalse($this->context->isProcessed(CheckRequestType::OPERATION_NAME));
            throw $e;
        }
    }
}
