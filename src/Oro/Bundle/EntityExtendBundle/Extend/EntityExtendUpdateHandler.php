<?php

namespace Oro\Bundle\EntityExtendBundle\Extend;

use Oro\Bundle\MaintenanceBundle\Maintenance\Mode as MaintenanceMode;

/**
 * The default implementation of the update schema handler.
 */
class EntityExtendUpdateHandler implements EntityExtendUpdateHandlerInterface
{
    /**
     * @param EntityExtendUpdateProcessor $entityExtendUpdateProcessor
     * @param MaintenanceMode             $maintenance
     */
    public function __construct(
        private EntityExtendUpdateProcessor $entityExtendUpdateProcessor,
        private MaintenanceMode $maintenance
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function update(): EntityExtendUpdateResult
    {
        $this->maintenance->activate();
        $updateResult = $this->entityExtendUpdateProcessor->processUpdate();

        return new EntityExtendUpdateResult($updateResult->isSuccessful());
    }
}
