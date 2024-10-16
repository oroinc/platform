<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Component\ChainProcessor\ParameterBagInterface;

class GetContextTest extends \PHPUnit\Framework\TestCase
{
    private GetContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new GetContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testParentAction(): void
    {
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));

        $actionName = 'test_action';
        $this->context->setParentAction($actionName);
        self::assertEquals($actionName, $this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertEquals($actionName, $this->context->get('parentAction'));

        $this->context->setParentAction(null);
        self::assertNull($this->context->getParentAction());
        self::assertTrue($this->context->has('parentAction'));
        self::assertSame('', $this->context->get('parentAction'));
    }

    public function testGetNormalizationContext(): void
    {
        $action = 'test_action';
        $version = '1.2';
        $sharedData = $this->createMock(ParameterBagInterface::class);
        $this->context->setAction($action);
        $this->context->setVersion($version);
        $this->context->setSharedData($sharedData);
        $this->context->getRequestType()->add('test_request_type');
        $requestType = $this->context->getRequestType();

        $normalizationContext = $this->context->getNormalizationContext();
        self::assertCount(4, $normalizationContext);
        self::assertSame($action, $normalizationContext['action']);
        self::assertSame($version, $normalizationContext['version']);
        self::assertSame($requestType, $normalizationContext['requestType']);
        self::assertSame($sharedData, $normalizationContext['sharedData']);

        $parentAction = 'test_parent_action';
        $this->context->setParentAction($parentAction);
        $normalizationContext = $this->context->getNormalizationContext();
        self::assertCount(5, $normalizationContext);
        self::assertSame($action, $normalizationContext['action']);
        self::assertSame($version, $normalizationContext['version']);
        self::assertSame($requestType, $normalizationContext['requestType']);
        self::assertSame($sharedData, $normalizationContext['sharedData']);
        self::assertSame($parentAction, $normalizationContext['parentAction']);
    }
}
