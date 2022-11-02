<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

/**
 * The default implementation of the update schema handler.
 */
class EntityExtendUpdateHandler implements EntityExtendUpdateHandlerInterface
{
    private EntityExtendUpdateProcessor $entityExtendUpdateProcessor;

    public function __construct(EntityExtendUpdateProcessor $entityExtendUpdateProcessor)
    {
        $this->entityExtendUpdateProcessor = $entityExtendUpdateProcessor;
    }

    /**
     * {@inheritDoc}
     */
    public function update(): EntityExtendUpdateResult
    {
        $updateResult = $this->entityExtendUpdateProcessor->processUpdate();

        return new EntityExtendUpdateResult($updateResult->isSuccessful());
    }
}
