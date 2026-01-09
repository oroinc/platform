<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

/**
 * Provides template fixture data for {@see AddressType} entities during import/export operations.
 *
 * This fixture generates sample address type records with appropriate labels derived
 * from the type name. It is used to populate import templates and demonstrate the
 * expected data structure for address type imports.
 */
class AddressTypeFixture extends AbstractTemplateRepository
{
    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\AddressType';
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new AddressType($key);
    }

    /**
     * @param string $key
     * @param AddressType $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        $label = ucfirst($entity->getName()) . ' Type';
        $entity->setLabel($label);
    }
}
