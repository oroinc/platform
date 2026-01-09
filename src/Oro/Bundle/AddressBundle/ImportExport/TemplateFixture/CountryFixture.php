<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

/**
 * Provides template fixture data for {@see Country} entities during import/export operations.
 *
 * This fixture generates sample country records for import templates. It handles the
 * special case of the United States (US) by skipping its default data generation,
 * allowing for custom US country configuration while providing standard data for
 * other countries.
 */
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
