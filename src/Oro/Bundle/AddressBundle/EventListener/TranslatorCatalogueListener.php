<?php

namespace Oro\Bundle\AddressBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Entity\Repository\IdentityAwareTranslationRepositoryInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepositoryInterface;
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

        $this->updateTranslations($catalogue, AddressType::class, 'address_type.');
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
        $repository = $this->getRepository($className);
        if (!$repository instanceof IdentityAwareTranslationRepositoryInterface) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected repository of type "%s", "%s" given',
                    IdentityAwareTranslationRepositoryInterface::class,
                    is_object($repository) ? get_class($repository) : gettype($repository)
                )
            );
        }

        $ids = $repository->getAllIdentities();

        $data = array_combine(
            $ids,
            array_map(
                function (string $id) use ($catalogue, $prefix) {
                    return $catalogue->get($prefix.$id, 'entities');
                },
                $ids
            )
        );

        if ($catalogue->getLocale() !== Translator::DEFAULT_LOCALE) {
            $repository = $this->getRepository($className.'Translation');
            if (!$repository instanceof TranslationRepositoryInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Expected repository of type "%s", "%s" given',
                        TranslationRepositoryInterface::class,
                        is_object($repository) ? get_class($repository) : gettype($repository)
                    )
                );
            }

            $repository->updateTranslations($data, $catalogue->getLocale());
        } else {
            $repository->updateTranslations($data);
        }
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
