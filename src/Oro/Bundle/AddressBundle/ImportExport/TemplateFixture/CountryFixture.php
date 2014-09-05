<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

class CountryFixture extends AbstractTemplateRepository
{
    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\Country';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new Country($key);
    }

    /**
     * @param string $key
     * @param Country $entity
     */
    public function fillEntityData($key, $entity)
    {
        if ($key === 'US') {
            return;
        }

        parent::fillEntityData($key, $entity);
    }
}
