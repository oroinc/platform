<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\MaxRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Shared\SetDefaultMaxRelatedEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class SetDefaultMaxRelatedEntitiesTest extends GetListProcessorTestCase
{
    private const MAX_RELATED_ENTITIES_LIMIT = 100;

    /** @var SetDefaultMaxRelatedEntities */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new SetDefaultMaxRelatedEntities(self::MAX_RELATED_ENTITIES_LIMIT);
    }

    public function testProcessWhenMaxRelatedEntitiesConfigExtraAlreadyExistsInContext()
    {
        $customMaxRelatedEntitiesLimit = 123;
        $existingExtra = new MaxRelatedEntitiesConfigExtra($customMaxRelatedEntitiesLimit);

        $this->context->addConfigExtra($existingExtra);
        $this->processor->process($this->context);

        /** @var MaxRelatedEntitiesConfigExtra $extra */
        $extra = $this->context->getConfigExtra(MaxRelatedEntitiesConfigExtra::NAME);
        self::assertSame($existingExtra, $extra);
        self::assertSame($customMaxRelatedEntitiesLimit, $extra->getMaxRelatedEntities());
    }

    public function testProcessWhenMaxRelatedEntitiesConfigExtraDoesNotExistInContext()
    {
        $this->processor->process($this->context);

        /** @var MaxRelatedEntitiesConfigExtra $extra */
        $extra = $this->context->getConfigExtra(MaxRelatedEntitiesConfigExtra::NAME);
        self::assertInstanceOf(MaxRelatedEntitiesConfigExtra::class, $extra);
        self::assertSame(self::MAX_RELATED_ENTITIES_LIMIT, $extra->getMaxRelatedEntities());
    }
}
