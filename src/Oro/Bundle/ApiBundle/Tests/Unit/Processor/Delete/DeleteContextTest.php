<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete;

use Oro\Bundle\ApiBundle\Processor\Delete\DeleteContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use PHPUnit\Framework\TestCase;

class DeleteContextTest extends TestCase
{
    private DeleteContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new DeleteContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testGetAllEntities(): void
    {
        self::assertSame([], $this->context->getAllEntities());

        $entity = new \stdClass();
        $this->context->setResult($entity);
        self::assertSame([$entity], $this->context->getAllEntities());
    }
}
