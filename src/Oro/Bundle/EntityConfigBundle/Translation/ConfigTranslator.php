<?php

namespace Oro\Bundle\EntityConfigBundle\Translation;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

class ConfigTranslator implements LoaderInterface
{
    /**
     * @var OroEntityManager
     */
    protected $em;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param ConfigManager $configManager
     * @param Translator $translator
     */
    public function __construct(ConfigManager $configManager, Translator $translator)
    {
        $this->configManager = $configManager;
        $this->translator    = $translator;
        $this->em            = $configManager->getEntityManager();
    }

    /**
     * Register custom translator
     *
     * @param Translator $translator
     */
    public function registerResources(Translator $translator)
    {
        $translator->addLoader('entity', $this);
        $translator->addResource('entity', $this, $translator->getLocale(), 'messages');
    }

    /**
     * Loads a locale dependent translations from config
     *
     * @param mixed $resource A resource
     * @param string $locale   A locale
     * @param string $domain   The domain
     *
     * @return MessageCatalogue A MessageCatalogue instance
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $messages = [];

        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        /** @var TranslationRepository $translationRepo */
        $translationRepo = $this->configManager->getEntityManager()->getRepository(Translation::ENTITY_NAME);

        /** @var Translation[] $translations */
        $translations = $translationRepo->findValues($this->translator->getLocale());
        foreach ($translations as $translation) {
            $messages[$translation->getKey()] = $translation->getValue();
        }

        $catalogue->add($messages);

        return $catalogue;
    }
}
