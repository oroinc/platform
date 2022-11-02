<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Provides various utility methods to work with translations.
 */
class TranslationProvider
{
    protected ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getMessageCatalogueByLocaleAndScope(string $locale, array $scopes = []): MessageCatalogue
    {
        $messages = [];
        $translations = $this->getRepository()->findAllByLanguageAndScopes($locale, $scopes);
        foreach ($translations as $translation) {
            $messages[$translation['domain']][$translation['key']] = $translation['value'];
        }

        $catalogue = new MessageCatalogue($locale);
        foreach ($messages as $domain => $translations) {
            $catalogue->add($translations, $domain);
        }

        return $catalogue;
    }

    private function getRepository(): TranslationRepository
    {
        return $this->doctrine->getRepository(Translation::class);
    }
}
