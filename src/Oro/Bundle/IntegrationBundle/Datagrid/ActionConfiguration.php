<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;

class ActionConfiguration
{
    /**
     * @var TypesRegistry
     */
    private $typesRegistry;

    /**
     * @param TypesRegistry $typesRegistry
     */
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

        if ($record->getValue('enabled') === 'disabled'
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
