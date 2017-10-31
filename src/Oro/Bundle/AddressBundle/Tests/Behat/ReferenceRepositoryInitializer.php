<?php

namespace Oro\Bundle\AddressBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityRepository;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\AddressTypeRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        /** @var EntityRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroAddressBundle:Country');
        /** @var Country $germany */
        $germany = $repository->findOneBy(['name' => 'Germany']);
        $referenceRepository->set('germany', $germany);
        /** @var Country $us */
        $us = $repository->findOneBy(['name' => 'United States']);
        $referenceRepository->set('united_states', $us);

        /** @var RegionRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroAddressBundle:Region');
        /** @var Region $berlin */
        $berlin = $repository->findOneBy(['name' => 'Berlin']);
        $referenceRepository->set('berlin', $berlin);

        /** @var Region $florida */
        $florida = $repository->findOneBy(['name' => 'Florida']);
        $referenceRepository->set('florida', $florida);

        /** @var AddressTypeRepository $repository */
        $repository = $doctrine->getManager()->getRepository('OroAddressBundle:AddressType');
        /** @var AddressType $billingType*/
        $billingType = $repository->findOneBy(['name' => 'billing']);
        $referenceRepository->set('billingType', $billingType);
        /** @var AddressType $shippingType*/
        $shippingType = $repository->findOneBy(['name' => 'shipping']);
        $referenceRepository->set('shippingType', $shippingType);
    }
}
