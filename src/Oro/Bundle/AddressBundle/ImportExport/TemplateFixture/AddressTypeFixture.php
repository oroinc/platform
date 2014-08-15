<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

class AddressTypeFixture extends AbstractTemplateRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\AddressType';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new AddressType($key);
    }

    /**
     * @param string $key
     * @param AddressType $entity
     */
    public function fillEntityData($key, $entity)
    {
        $label = ucfirst($entity->getName()) . ' Type';
        $entity->setLabel($label);
    }
}
