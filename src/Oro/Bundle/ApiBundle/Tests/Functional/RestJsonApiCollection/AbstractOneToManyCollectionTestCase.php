<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCollection;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollection;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCollectionItem;

abstract class AbstractOneToManyCollectionTestCase extends AbstractCollectionTestCase
{
    #[\Override]
    protected function getCollectionEntityClass(): string
    {
        return TestCollection::class;
    }

    #[\Override]
    protected function getCollectionItemEntityClass(): string
    {
        return TestCollectionItem::class;
    }

    #[\Override]
    protected function isManyToMany(): bool
    {
        return false;
    }
}
