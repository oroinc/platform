<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class DeleteListContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigProvider */
    private $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|MetadataProvider */
    private $metadataProvider;

    /** @var SingleItemContext */
    private $context;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->metadataProvider = $this->createMock(MetadataProvider::class);

        $this->context = new DeleteListContext($this->configProvider, $this->metadataProvider);
    }

    public function testGetAllEntities()
    {
        self::assertSame([], $this->context->getAllEntities());

        $entities = [new \stdClass()];
        $this->context->setResult($entities);
        self::assertSame($entities, $this->context->getAllEntities());
    }
}
