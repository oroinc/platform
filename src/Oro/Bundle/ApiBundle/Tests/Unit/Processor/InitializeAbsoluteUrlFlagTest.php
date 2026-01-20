<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\InitializeAbsoluteUrlFlag;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class InitializeAbsoluteUrlFlagTest extends TestCase
{
    private RequestStack $requestStack;
    private ContextInterface&MockObject $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->context = $this->createMock(ContextInterface::class);
    }

    public function testProcessWhenConfigurationDisabled(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, false);
        $processor->process($this->context);

        self::assertFalse($request->attributes->has(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG));
    }

    public function testProcessWhenConfigurationEnabledAndRequestExists(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, true);
        $processor->process($this->context);

        self::assertTrue($request->attributes->has(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG));
        self::assertTrue($request->attributes->get(InitializeAbsoluteUrlFlag::ABSOLUTE_URL_FLAG));
    }

    public function testProcessWhenConfigurationEnabledButNoRequest(): void
    {
        $processor = new InitializeAbsoluteUrlFlag($this->requestStack, true);
        $processor->process($this->context);
        self::assertNull($this->requestStack->getCurrentRequest());
    }
}
