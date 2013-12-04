<?php

namespace Oro\Bundle\EntityBundle\Translation;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Translation\Translator;

class ConfigTranslator implements LoaderInterface
{
    protected $em;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->em = $configManager->getEntityManager();
    }

    /**
     * Loads a locale.
     *
     * @param mixed $resource A resource
     * @param string $locale   A locale
     * @param string $domain   The domain
     *
     * @return MessageCatalogue A MessageCatalogue instance
     *
     * @api
     *
     * @throws NotFoundResourceException when the resource cannot be found
     * @throws InvalidResourceException  when the resource cannot be loaded
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $resource = $resource;


        //Load on the db for the specified local
        //$language = $this->languageRepository->getLanguage($locale);

        //$translations = $this->transaltionRepository->getTranslations($language, $domain);

        //$catalogue = new MessageCatalogue($locale);

        /** @ var $translation Frtrains\CommonbBundle\Entity\LanguageTranslation */
        /*foreach($translations as $translation){
            $catalogue->set($translation->getLanguageToken()->getToken(), $translation->getTranslation(), $domain);
        }*/

        //return $catalogue;
    }

    /**
     * Retrieve all locale-domain combinations and add them as a resource to the translator.
     *
     * @param Translator $translator
     *
     * @throws \RuntimeException
     */
    public function registerResources(Translator $translator)
    {
        /*
        $stmt = $this->getResourcesStatement();
        if (false === $stmt->execute()) {
            throw new \RuntimeException('Could not fetch translation data from database.');
        }
        $stmt->bindColumn('locale', $locale);
        $stmt->bindColumn('domain', $domain);
        */


        $translator->addResource('pdo', $this, $locale, $domain);

    }
}
