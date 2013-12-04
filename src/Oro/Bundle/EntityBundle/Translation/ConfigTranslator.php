<?php

namespace Oro\Bundle\EntityBundle\Translation;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

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
        $messages               = [];
        $translatorMessagesKeys = array_keys($this->translator->getTranslations()['messages']);

        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        /** @var ConfigProvider */
        $entityProvider = $this->configManager->getProvider('entity');

        $configEntities = $entityProvider->getIds();
        foreach ($configEntities as $entity) {
            $entityFields = $entityProvider->getIds($entity->getClassName());
            foreach ($entityFields as $field) {
                $value      = $entityProvider->getConfigById($field)->get('label');
                $class      = str_replace(['Bundle\\Entity', 'Bundle\\'], '', $field->getClassName());
                $classArray = explode('\\', $class);
                $keyArray   = [];

                foreach ($classArray as $item) {
                    if (!in_array(Inflector::camelize($item), $keyArray)) {
                        $keyArray[] = Inflector::camelize($item);
                    }
                }
                $keyArray[] = Inflector::tableize($field->getFieldName());

                $key = implode('.', $keyArray);
                if ($value != Inflector::tableize($field->getFieldName())
                    || !in_array($key, $translatorMessagesKeys)
                ) {
                    $messages[$key] = $value;
                }
            }
        }

        $catalogue->add($messages);

        return $catalogue;
    }
}
