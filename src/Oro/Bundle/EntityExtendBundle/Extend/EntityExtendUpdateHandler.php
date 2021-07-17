<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * The default implementation of the update schema handler.
 */
class EntityExtendUpdateHandler implements EntityExtendUpdateHandlerInterface
{
    /** @var EntityExtendUpdateProcessor */
    private $entityExtendUpdateProcessor;

    public function __construct(EntityExtendUpdateProcessor $entityExtendUpdateProcessor)
    {
        $this->entityExtendUpdateProcessor = $entityExtendUpdateProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function update(): EntityExtendUpdateResult
    {
        return new EntityExtendUpdateResult($this->entityExtendUpdateProcessor->processUpdate());
    }
}
