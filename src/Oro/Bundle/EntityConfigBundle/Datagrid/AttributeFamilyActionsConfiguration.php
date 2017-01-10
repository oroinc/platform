<?php

namespace Oro\Bundle\EntityConfigBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeFamilyManager;
use Oro\Bundle\EntityConfigBundle\Voter\AttributeFamilyVoter;

class AttributeFamilyActionsConfiguration
{
    /** @var AttributeFamilyVoter */
    private $familyManager;

    /**
     * @param AttributeFamilyManager $familyManager
     */
    public function __construct(AttributeFamilyManager $familyManager)
    {
        $this->familyManager = $familyManager;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function configureActionsVisibility(ResultRecordInterface $record)
    {
        $id = $record->getValue('id');

        return [
            'view' => true,
            'edit' => true,
            'delete' => $this->familyManager->isAttributeFamilyDeletable($id)
        ];
    }
}
