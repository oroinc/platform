<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList;

use Oro\Bundle\ApiBundle\Processor\DeleteList\DeleteListContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class DeleteListContextTest extends TestCase
{
    private DeleteListContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new DeleteListContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testGetAllEntities(): void
    {
        self::assertSame([], $this->context->getAllEntities());

        $entities = [new \stdClass()];
        $this->context->setResult($entities);
        self::assertSame($entities, $this->context->getAllEntities());
    }
}
