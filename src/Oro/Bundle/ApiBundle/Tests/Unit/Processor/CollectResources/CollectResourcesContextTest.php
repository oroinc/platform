<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Request\ApiResourceCollection;
use PHPUnit\Framework\TestCase;

class CollectResourcesContextTest extends TestCase
{
    private CollectResourcesContext $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = new CollectResourcesContext();
    }

    public function testResultShouldBeInitialized(): void
    {
        self::assertInstanceOf(ApiResourceCollection::class, $this->context->getResult());
    }

    public function testAccessibleResources(): void
    {
        self::assertEquals([], $this->context->getAccessibleResources());

        $this->context->setAccessibleResources(['Test\Class']);
        self::assertEquals(['Test\Class'], $this->context->getAccessibleResources());
    }

    public function testAccessibleAsAssociationResources(): void
    {
        self::assertEquals([], $this->context->getAccessibleAsAssociationResources());

        $this->context->setAccessibleAsAssociationResources(['Test\Class']);
        self::assertEquals(['Test\Class'], $this->context->getAccessibleAsAssociationResources());
    }
}
