<?php

namespace Oro\Bundle\AddressBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\CountryTranslationRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionRepository;
use Oro\Bundle\AddressBundle\Entity\Repository\RegionTranslationRepository;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueDump;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Fill Gedmo\Translatable dictionaries for Country and Region entities on finalizing translation catalogue build.
 */
class TranslatorCatalogueListener
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param AfterCatalogueDump $event
     */
    public function onAfterCatalogueDump(AfterCatalogueDump $event)
    {
        $catalogue = $event->getCatalogue();

        $this->updateTranslations($catalogue, Country::class, 'country.');
        $this->updateTranslations($catalogue, Region::class, 'region.');
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     * @param string $className
     * @param string $prefix
     */
    private function updateTranslations(MessageCatalogueInterface $catalogue, string $className, string $prefix)
    {
        /** @var CountryRepository|RegionRepository $repository */
        $repository = $this->getRepository($className);

        $ids = $repository->getAllIdentities();

        $data = array_combine(
            $ids,
            array_map(
                function (string $id) use ($catalogue, $prefix) {
                    return $catalogue->get($prefix . $id, 'entities');
                },
                $ids
            )
        );

        if ($catalogue->getLocale() !== Translator::DEFAULT_LOCALE) {
            /** @var CountryTranslationRepository|RegionTranslationRepository $repository */
            $repository = $this->getRepository($className . 'Translation');
        }

        $repository->updateTranslations($data, $catalogue->getLocale());
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    private function getRepository(string $className)
    {
        return $this->registry->getManagerForClass($className)->getRepository($className);
    }
}
