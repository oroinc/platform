<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\Rest\SetWwwAuthenticateResponseHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\UnhandledError\UnhandledErrorProcessorTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SetWwwAuthenticateResponseHeaderTest extends UnhandledErrorProcessorTestCase
{
    /** @var SetWwwAuthenticateResponseHeader */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetWwwAuthenticateResponseHeader();
    }

    public function testProcessWhenNoErrors(): void
    {
        $this->processor->process($this->context);
        self::assertCount(0, $this->context->getResponseHeaders());
    }

    public function testProcessWhenNoUnauthorizedError(): void
    {
        $this->context->addError(Error::create('err1')->setStatusCode(400));
        $this->processor->process($this->context);
        self::assertCount(0, $this->context->getResponseHeaders());
    }

    public function testProcessForUnauthorizedErrorWithoutWwwAuthenticateHeader(): void
    {
        $unauthorizedException = new HttpException(401, '', null, []);
        $this->context->addError(Error::create('err1')->setStatusCode(401)->setInnerException($unauthorizedException));
        $this->processor->process($this->context);
        self::assertCount(0, $this->context->getResponseHeaders());
    }

    /**
     * @dataProvider wwwAuthenticateHeaderName
     */
    public function testProcessForUnauthorizedErrorWithWwwAuthenticateHeader(string $wwwAuthenticateHeaderName): void
    {
        $unauthorizedException = new HttpException(401, '', null, [$wwwAuthenticateHeaderName => 'Test Value']);
        $this->context->addError(Error::create('err1')->setStatusCode(401)->setInnerException($unauthorizedException));
        $this->processor->process($this->context);
        self::assertCount(1, $this->context->getResponseHeaders());
        self::assertEquals('Test Value', $this->context->getResponseHeaders()->get('WWW-Authenticate'));
    }

    public function wwwAuthenticateHeaderName(): array
    {
        return [
            ['WWW-Authenticate'],
            ['www-authenticate'],
            ['WWW-AUTHENTICATE']
        ];
    }

    public function testProcessWhenWwwAuthenticateHeaderAlreadySet(): void
    {
        $unauthorizedException = new HttpException(401, '', null, ['WWW-Authenticate' => 'Test Value']);
        $this->context->addError(Error::create('err1')->setStatusCode(401)->setInnerException($unauthorizedException));
        $this->context->getResponseHeaders()->set('WWW-Authenticate', 'Other Value');
        $this->processor->process($this->context);
        self::assertCount(1, $this->context->getResponseHeaders());
        self::assertEquals('Other Value', $this->context->getResponseHeaders()->get('WWW-Authenticate'));
    }
}
