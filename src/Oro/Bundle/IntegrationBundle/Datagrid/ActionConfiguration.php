<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;

/**
 * Provides configuration for datagrid actions based on integration channel state.
 *
 * This class determines which actions (schedule sync, activate, deactivate, delete) should be
 * available for each integration channel in the datagrid, based on the channel's enabled state,
 * integration type capabilities, and edit mode restrictions.
 */
class ActionConfiguration
{
    /**
     * @var TypesRegistry
     */
    private $typesRegistry;

    public function __construct(TypesRegistry $typesRegistry)
    {
        $this->typesRegistry = $typesRegistry;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return array
     */
    public function getIsSyncAvailableCondition(ResultRecordInterface $record)
    {
        $result = [];

        if (
            $record->getValue('enabled') === 'disabled'
            || false === $this->typesRegistry->supportsSync($record->getValue('type'))
        ) {
            $result['schedule'] = false;
        }

        $editMode = $record->getValue('editMode');

        if (EditModeUtils::isSwitchEnableAllowed($editMode)) {
            if ($record->getValue('enabled') === 'disabled') {
                $result['deactivate'] = false;
            } else {
                $result['activate'] = false;
            }
        } else {
            $result['activate'] = false;
            $result['deactivate'] = false;
        }
        $result['delete'] = EditModeUtils::isEditAllowed($editMode);

        return $result;
    }
}
