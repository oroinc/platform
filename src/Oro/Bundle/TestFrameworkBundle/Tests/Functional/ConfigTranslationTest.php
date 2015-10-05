<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class ConfigTranslationTest extends WebTestCase
{
    /** @var Translator */
    protected $translator;

    /**
     * {@inheritdoc}
     */
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

                /**
                 * We should not check translations of entities being created/used only in test environment.
                 * It's done to avoid adding and accumulation of unnecessary test entity/field/relation translations.
                 */
                if (isset($field['related_entity_name'])
                    && is_a(
                        $field['related_entity_name'],
                        'Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface',
                        true
                    )
                ) {
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
                $configId = $config->getId();
                if ($configId instanceof FieldConfigId) {
                    $transKey .= sprintf(
                        ' [Entity: %s; Field: %s]',
                        $configId->getClassName(),
                        $configId->getFieldName()
                    );
                } else {
                    $transKey .= sprintf(' [Entity: %s]', $configId->getClassName());
                }
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
