<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Translation loader that load translations from database for correct dump translation files
 */
class OrmTranslationLoader implements LoaderInterface
{
    public function __construct(
        private Registry $registry
    ) {
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $translationRepository = $this->registry->getRepository(Translation::class);
        $translations = $translationRepository->findDomainTranslations($locale, $domain);

        if (!empty($translations)) {
            $catalogue->add(array_combine(
                array_column($translations, 'key'),
                array_column($translations, 'value')
            ), $domain);
        }

        return $catalogue;
    }
}
