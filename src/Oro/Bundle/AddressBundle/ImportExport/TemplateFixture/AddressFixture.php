<?php

namespace Oro\Bundle\AddressBundle\ImportExport\TemplateFixture;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;

/**
 * Provides template fixture data for {@see Address} entities during import/export operations.
 *
 * This fixture generates sample address records with realistic data for different
 * individuals (Jerry Coleman, John Smith, John Doo), including street addresses,
 * postal codes, cities, regions, and countries. It is used to populate import templates
 * and demonstrate the expected data structure for address imports.
 */
class AddressFixture extends AbstractTemplateRepository
{
    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\AddressBundle\Entity\Address';
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new Address();
    }

    /**
     * @param string  $key
     * @param Address $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        $countryRepo = $this->templateManager
            ->getEntityRepository('Oro\Bundle\AddressBundle\Entity\Country');
        $regionRepo  = $this->templateManager
            ->getEntityRepository('Oro\Bundle\AddressBundle\Entity\Region');

        switch ($key) {
            case 'Jerry Coleman':
                $entity->setCity('Rochester')
                    ->setStreet('1215 Caldwell Road')
                    ->setPostalCode('14608')
                    ->setFirstName('Jerry')
                    ->setLastName('Coleman')
                    ->setRegion($regionRepo->getEntity('NY'))
                    ->setCountry($countryRepo->getEntity('US'));
                return;
            case 'John Smith':
                $entity->setCity('New York')
                    ->setStreet('4677 Pallet Street')
                    ->setPostalCode('10011')
                    ->setFirstName('John')
                    ->setLastName('Smith')
                    ->setRegion($regionRepo->getEntity('NY'))
                    ->setCountry($countryRepo->getEntity('US'));
                return;
            case 'John Doo':
                $entity->setCity('New York')
                    ->setStreet('52 Jarvisville Road')
                    ->setPostalCode('11590')
                    ->setFirstName('John')
                    ->setLastName('Doo')
                    ->setRegion($regionRepo->getEntity('NY'))
                    ->setCountry($countryRepo->getEntity('US'));
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
