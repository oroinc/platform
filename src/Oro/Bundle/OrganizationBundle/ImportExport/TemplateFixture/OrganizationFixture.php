<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationFixture extends AbstractTemplateRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\OrganizationBundle\Entity\Organization';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new Organization();
    }

    /**
     * @param string       $key
     * @param Organization $entity
     */
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case 'default':
                $entity->setName($key);
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
