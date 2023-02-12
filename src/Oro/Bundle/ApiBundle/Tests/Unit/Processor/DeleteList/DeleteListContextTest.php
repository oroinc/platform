<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class DeleteListContextTest extends \PHPUnit\Framework\TestCase
{
    private DeleteListContext $context;

    protected function setUp(): void
    {
        $this->context = new DeleteListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testGetAllEntities()
    {
        self::assertSame([], $this->context->getAllEntities());

        $entities = [new \stdClass()];
        $this->context->setResult($entities);
        self::assertSame($entities, $this->context->getAllEntities());
    }
}
