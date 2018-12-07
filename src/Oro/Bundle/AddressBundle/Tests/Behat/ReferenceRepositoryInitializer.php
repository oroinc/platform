<?php

namespace Oro\Bundle\AddressBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /** @var array */
    private $data = [
        Country::class => [
            'germany' => ['iso2Code' => 'DE'],
            'austria' => ['iso2Code' => 'AT'],
            'united_states' => ['iso2Code' => 'US'],
        ],
        Region::class => [
            'berlin' => ['combinedCode' => 'DE-BE'],
            'vienna' => ['combinedCode' => 'AT-9'],
            'florida' => ['combinedCode' => 'US-FL'],
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
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        foreach ($this->data as $className => $entities) {
            $repository = $doctrine->getManager()->getRepository($className);

            foreach ($entities as $referenceName => $criteria) {
                $referenceRepository->set($referenceName, $repository->findOneBy($criteria));
            }
        }
    }
}
