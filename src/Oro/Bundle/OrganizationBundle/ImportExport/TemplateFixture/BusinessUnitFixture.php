<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Provides template fixture data for business unit import/export operations.
 *
 * This fixture generates sample business unit data for import templates, allowing users to
 * understand the expected format for bulk importing business units. It includes a predefined
 * "Main" business unit that serves as a reference example.
 */
class BusinessUnitFixture extends AbstractTemplateRepository
{
    const MAIN_BUSINESS_UNIT = 'Main';

    #[\Override]
    public function getEntityClass()
    {
        return BusinessUnit::class;
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new BusinessUnit();
    }

    /**
     * @param string       $key
     * @param BusinessUnit $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case self::MAIN_BUSINESS_UNIT:
                $entity->setName($key);
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
