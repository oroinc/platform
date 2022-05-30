<?php

namespace Oro\Bundle\AddressBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /** @var array */
    private $data = [
        Country::class => [
            'germany' => ['iso2Code' => 'DE'],
            'austria' => ['iso2Code' => 'AT'],
            'samoa' => ['iso2Code' => 'AS'],
            'united_states' => ['iso2Code' => 'US'],
            'monaco' => ['iso2Code' => 'MC'],
        ],
        Region::class => [
            'berlin' => ['combinedCode' => 'DE-BE'],
            'vienna' => ['combinedCode' => 'AT-9'],
            'florida' => ['combinedCode' => 'US-FL'],
            'new_york' => ['combinedCode' => 'US-NY'],
            'indiana' => ['combinedCode' => 'US-IN'],
            'california' => ['combinedCode' => 'US-CA'],
        ],
        AddressType::class => [
            'billingType' => ['name' => 'billing'],
            'shippingType' => ['name' => 'shipping'],
        ],
        TranslationKey::class => [
            'translation_key_oro_country_DE' => ['key' => 'country.DE', 'domain' => 'entities'],
            'translation_key_oro_country_AT' => ['key' => 'country.AT', 'domain' => 'entities'],
            'translation_key_oro_country_US' => ['key' => 'country.US', 'domain' => 'entities'],
            'translation_key_oro_region_DE-BE' => ['key' => 'region.DE-BE', 'domain' => 'entities'],
            'translation_key_oro_region_AT-9' => ['key' => 'region.AT-9', 'domain' => 'entities'],
            'translation_key_oro_region_US-FL' => ['key' => 'region.US-FL', 'domain' => 'entities'],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        foreach ($this->data as $className => $entities) {
            $repository = $doctrine->getManager()->getRepository($className);
            foreach ($entities as $referenceName => $criteria) {
                $referenceRepository->set($referenceName, $repository->findOneBy($criteria));
            }
        }
    }
}
