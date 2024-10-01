<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ResponseStatusCodeContextConfigurator;
use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;

class ResponseStatusCodeContextConfiguratorTest extends TestCase
{
    private ResponseStatusCodeContextConfigurator $configurator;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurator = new ResponseStatusCodeContextConfigurator();
    }

    public function testConfigureContextHasDefaultStatusCode(): void
    {
        $context = new LayoutContext();

        $this->configurator->configureContext($context);

        $context->resolve();

        self::assertTrue($context->has('response_status_code'));
        self::assertEquals(200, $context->get('response_status_code'));
    }

    public function testConfigureContextAcceptsExplicitStatusCode(): void
    {
        $context = new LayoutContext(['response_status_code' => 422]);

        $this->configurator->configureContext($context);

        $context->resolve();

        self::assertTrue($context->has('response_status_code'));
        self::assertEquals(422, $context->get('response_status_code'));
    }

    public function testConfigureContextAcceptsOnlyIntStatusCode(): void
    {
        $context = new LayoutContext(['response_status_code' => 'invalid']);

        $this->configurator->configureContext($context);

        $this->expectExceptionObject(
            new LogicException(
                'The option "response_status_code" with value "invalid" is expected to be of type "int", '
                . 'but is of type "string".'
            )
        );

        $context->resolve();
    }
}
