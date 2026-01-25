<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Shared\InitializeAbsoluteUrlFlag;
use Oro\Bundle\ApiBundle\Provider\ApiUrlResolver;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InitializeAbsoluteUrlFlagTest extends GetListProcessorTestCase
{
    private RequestStack $requestStack;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = new RequestStack();
    }

    public function testProcessWhenConfigurationDisabled(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, false);
        $processor->process($this->context);

        self::assertFalse($request->attributes->has(ApiUrlResolver::ABSOLUTE_URL_FLAG));
    }

    public function testProcessWhenConfigurationEnabled(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, true);
        $processor->process($this->context);

        self::assertTrue($request->attributes->has(ApiUrlResolver::ABSOLUTE_URL_FLAG));
        self::assertTrue($request->attributes->get(ApiUrlResolver::ABSOLUTE_URL_FLAG));
    }

    public function testProcessWhenConfigurationEnabledButNoRequest(): void
    {
        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, true);
        $processor->process($this->context);

        self::assertNull($this->requestStack->getCurrentRequest());
    }
}
