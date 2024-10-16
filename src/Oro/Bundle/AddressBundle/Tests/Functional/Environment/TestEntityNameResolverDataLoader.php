<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Address::class === $entityClass) {
            $address = new Address();
            $address->setOrganization($repository->getReference('organization'));
            $address->setFirstName('Jane');
            $address->setMiddleName('M');
            $address->setLastName('Doo');
            $repository->setReference('address', $address);
            $em->persist($address);
            $em->flush();

            return ['address'];
        }

        if (Country::class === $entityClass) {
            $country = $em->find(Country::class, 'US');
            $repository->setReference('country', $country);
            $em->persist($country);
            $em->flush();

            return ['country'];
        }

        if (Region::class === $entityClass) {
            $region = $em->find(Region::class, 'US-CA');
            $repository->setReference('region', $region);
            $em->persist($region);
            $em->flush();

            return ['region'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Address::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? 'Jane'
                : 'Jane M Doo';
        }
        if (Country::class === $entityClass) {
            return 'United States';
        }
        if (Region::class === $entityClass) {
            return 'California';
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }
}
