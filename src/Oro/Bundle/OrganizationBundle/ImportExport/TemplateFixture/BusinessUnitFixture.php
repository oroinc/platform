<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class BusinessUnitFixture extends AbstractTemplateRepository
{
    public const MAIN_BUSINESS_UNIT = 'Main';

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
