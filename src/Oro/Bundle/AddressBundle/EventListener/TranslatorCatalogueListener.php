<?php

namespace Oro\Bundle\AddressBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AddressBundle\Entity\AddressTypeTranslation;
use Oro\Bundle\AddressBundle\Entity\CountryTranslation;
use Oro\Bundle\AddressBundle\Entity\RegionTranslation;
use Oro\Bundle\TranslationBundle\Entity\Repository\AbstractTranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;
use Oro\Bundle\TranslationBundle\Event\AfterCatalogueInitialize;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\MessageCatalogueInterface;

/**
 * Fill Gedmo\Translatable dictionaries for Country and Region entities on finalizing translation catalogue build.
 */
class TranslatorCatalogueListener
{
    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    public function onAfterCatalogueInit(AfterCatalogueInitialize $event)
    {
        $catalogue = $event->getCatalogue();

        $this->updateTranslations($catalogue, AddressTypeTranslation::class, 'address_type.');
        $this->updateTranslations($catalogue, CountryTranslation::class, 'country.');
        $this->updateTranslations($catalogue, RegionTranslation::class, 'region.');
    }

    private function updateTranslations(MessageCatalogueInterface $catalogue, string $className, string $prefix)
    {
        if (!in_array('entities', $catalogue->getDomains())) {
            return;
        }
        /** @var AbstractTranslationRepository $repository */
        $repository = $this->getRepository($className);

        $ids = $repository->getAllIdentities();
        $translations = $repository->findDomainTranslations($catalogue->getLocale(), 'entities');
        $translations = array_combine(
            array_column($translations, 'key'),
            array_column($translations, 'value')
        );

        $data = array_combine(
            $ids,
            array_map(
                function (string $id) use ($catalogue, $prefix, $translations) {
                    $value = $catalogue->get($prefix.$id, 'entities');
                    if (!$catalogue->defines($prefix.$id, 'entities')) {
                        $value = $translations[$prefix.$id] ?? $value;
                    }
                    $translations[$prefix.$id] = $value;
                    return $value;
                },
                $ids
            )
        );

        if ($catalogue->getLocale() === Translator::DEFAULT_LOCALE) {
            $repository->updateDefaultTranslations($data);
        } else {
            $repository->updateTranslations($data, $catalogue->getLocale());
        }
    }

    /**
     * @param string $className
     * @return TranslationRepositoryInterface
     */
    private function getRepository(string $className): TranslationRepositoryInterface
    {
        $repository = $this->registry->getManagerForClass($className)->getRepository($className);

        if (!$repository instanceof TranslationRepositoryInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected repository of type "%s", "%s" given',
                    TranslationRepositoryInterface::class,
                    is_object($repository) ? get_class($repository) : gettype($repository)
                )
            );
        }

        return $repository;
    }
}
