<?php

namespace Oro\Bundle\EntityExtendBundle\Test;

/**
 * This trait can be used in unit tests that need to check expectations for extended fields.
 */
trait ExtendedEntityTestTrait
{
    protected EntityFieldTestExtension $entityFieldTestExtension;

    /**
     * @before
     */
    protected function addEntityFieldTestExtension(): void
    {
        $this->entityFieldTestExtension = new EntityFieldTestExtension();
        EntityExtendTestInitializer::addExtension($this->entityFieldTestExtension);
    }

    /**
     * @after
     */
    protected function removeEntityFieldTestExtension(): void
    {
        EntityExtendTestInitializer::removeExtension($this->entityFieldTestExtension);
    }
}
