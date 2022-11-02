<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\RequestType;

class DeleteProcessorTestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEST_VERSION = '1.1';
    protected const TEST_REQUEST_TYPE = RequestType::REST;

    protected DeleteContext $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    protected $metadataProvider;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new DeleteContext($this->configProvider, $this->metadataProvider);
        $this->context->setAction(ApiAction::DELETE);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
    }
}
