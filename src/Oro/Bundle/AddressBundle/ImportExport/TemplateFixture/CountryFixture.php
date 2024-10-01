<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

class CountryFixture extends AbstractTemplateRepository
{
    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\Country';
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new Country($key);
    }

    /**
     * @param string $key
     * @param Country $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        if ($key === 'US') {
            return;
        }

        parent::fillEntityData($key, $entity);
    }
}
