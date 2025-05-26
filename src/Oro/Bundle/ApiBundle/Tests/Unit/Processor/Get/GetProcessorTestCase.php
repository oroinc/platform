<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetProcessorTestCase extends TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected GetContext $context;
    protected ConfigProvider&MockObject $configProvider;
    protected MetadataProvider&MockObject $metadataProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new GetContext($this->configProvider, $this->metadataProvider);
        $this->context->setAction(ApiAction::GET);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras([
            new EntityDefinitionConfigExtra($this->context->getAction()),
            new FiltersConfigExtra()
        ]);
    }
}
