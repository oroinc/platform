<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GetListProcessorOrmRelatedTestCase extends OrmRelatedTestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected GetListContext $context;
    protected ConfigProvider&MockObject $configProvider;
    protected MetadataProvider&MockObject $metadataProvider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new GetListContext($this->configProvider, $this->metadataProvider);
        $this->context->setAction(ApiAction::GET_LIST);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras([
            new EntityDefinitionConfigExtra($this->context->getAction()),
            new FiltersConfigExtra(),
            new SortersConfigExtra()
        ]);
    }
}
