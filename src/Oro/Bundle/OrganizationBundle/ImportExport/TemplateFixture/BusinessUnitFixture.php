<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class BusinessUnitFixture extends AbstractTemplateRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new BusinessUnit();
    }

    /**
     * @param string       $key
     * @param BusinessUnit $entity
     */
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case 'Main':
                $entity->setName($key);
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
