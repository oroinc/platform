<?php

namespace Oro\Bundle\EntityBundle\Translation;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

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
        //Load on the db for the specified local
        $language = $this->languageRepository->getLanguage($locale);

        $translations = $this->transaltionRepository->getTranslations($language, $domain);

        $catalogue = new MessageCatalogue($locale);

        /** @ var $translation Frtrains\CommonbBundle\Entity\LanguageTranslation */
        /*foreach($translations as $translation){
            $catalogue->set($translation->getLanguageToken()->getToken(), $translation->getTranslation(), $domain);
        }*/

        return $catalogue;
    }



}
