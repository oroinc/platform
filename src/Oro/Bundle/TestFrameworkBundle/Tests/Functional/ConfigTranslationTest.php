<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class ConfigTranslationTest extends WebTestCase
{
    /**
     * @var Translator
     */
    protected $translator;

    protected function setUp()
    {
        $this->initClient();
    }

    public function testConfigTranslationKeysExists()
    {
        $configProvider = $this->getContainer()->get('oro_entity_config.provider.entity');
        $provider = $this->getContainer()->get('oro_test.entity_field_list_provider');

        $fields = $provider->getFields(true, true, true, true, false);
        $missingTranslationKeys = [];

        foreach ($fields as $className => $options) {
            $entityConfig = $configProvider->getConfig($className);
            $missingTranslationKeys = array_merge(
                $missingTranslationKeys,
                $this->getMissingTranslationKeys($entityConfig)
            );

            foreach ($options['fields'] as $field) {
                if (!$configProvider->hasConfig($className, $field['name'])) {
                    continue;
                }

                $fieldConfig = $configProvider->getConfig($className, $field['name']);
                $missingTranslationKeys = array_merge(
                    $missingTranslationKeys,
                    $this->getMissingTranslationKeys($fieldConfig)
                );
            }
        }

        $this->assertEmpty($missingTranslationKeys, implode("\n", $missingTranslationKeys));
    }

    /**
     * @param ConfigInterface $config
     *
     * @return array
     */
    protected function getMissingTranslationKeys(ConfigInterface $config)
    {
        $keys = ['label'];
        if ($config->getId() instanceof EntityConfigId) {
            $keys[] = 'plural_label';
        }

        $missingTranslationKeys = [];
        foreach ($keys as $key) {
            $transKey = $config->get($key);
            if (!$this->getTranslator()->hasTrans($transKey)) {
                $missingTranslationKeys[] = $transKey;
            }
        }

        return $missingTranslationKeys;
    }

    /**
     * @return Translator
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->getContainer()->get('translator');
        }

        return $this->translator;
    }
}
