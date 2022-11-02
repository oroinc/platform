<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @group schema
 */
class ConfigTranslationTest extends WebTestCase
{
    /** @var Translator */
    private $translator;

    /** @var ConfigManager */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->translator = self::getContainer()->get('translator');
        $this->configManager = self::getContainer()->get('oro_entity_config.config_manager');

        /**
         * uncomment this line if you have added missing translations
         * and do not want to run cache:clear after that
         */
        //$this->translator->rebuildCache();
    }

    public function testConfigurableEntitiesTranslationKeysShouldExist()
    {
        $missingTranslationKeys = [];
        $entityConfigs = $this->configManager->getConfigs('entity');
        foreach ($entityConfigs as $entityConfig) {
            $entityClass = $entityConfig->getId()->getClassName();
            if ($this->isTestEntity($entityClass)) {
                continue;
            }

            $this->addMissingTranslationKeysForEntity($missingTranslationKeys, $entityConfig);

            $fieldConfigs = $this->configManager->getConfigs('entity', $entityClass);
            foreach ($fieldConfigs as $fieldConfig) {
                $fieldName = $fieldConfig->getId()->getFieldName();
                $extendFieldConfig = $this->configManager->getFieldConfig('extend', $entityClass, $fieldName);
                if ($extendFieldConfig->has('target_entity')
                    && $this->isTestEntity($extendFieldConfig->get('target_entity'))
                ) {
                    continue;
                }

                $this->addMissingTranslationKeysForField($missingTranslationKeys, $fieldConfig);
            }
        }

        $this->assertMissingTranslationKeysEmpty($missingTranslationKeys);
    }

    /**
     * @depends testConfigurableEntitiesTranslationKeysShouldExist
     */
    public function testVirtualFieldsTranslationKeysShouldExist()
    {
        $missingTranslationKeys = [];

        $fields = self::getContainer()->get('oro_test.entity_field_list_provider')
            ->getFields(true, true, true, true, true, false);
        foreach ($fields as $entityClass => $options) {
            foreach ($options['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldTranslatedLabel = $field['label'];
                if (isset($field['related_entity_name']) && $this->isTestEntity($field['related_entity_name'])) {
                    continue;
                }

                if (false !== strpos($fieldName, '::')) {
                    $this->addMissingTranslationKeysForUnidirectionalAssociation(
                        $missingTranslationKeys,
                        $entityClass,
                        $fieldName,
                        $fieldTranslatedLabel
                    );
                } else {
                    $this->addMissingTranslationKeysForVirtualField(
                        $missingTranslationKeys,
                        $entityClass,
                        $fieldName,
                        $fieldTranslatedLabel
                    );
                }
            }
        }

        $this->assertMissingTranslationKeysEmpty($missingTranslationKeys);
    }

    public function assertMissingTranslationKeysEmpty(array $missingTranslationKeys)
    {
        if ($missingTranslationKeys) {
            self::fail(sprintf(
                "Found %d missing translations:\n%s",
                count($missingTranslationKeys),
                implode("\n", $missingTranslationKeys)
            ));
        }
    }

    private function addMissingTranslationKeysForEntity(array &$missingTranslationKeys, ConfigInterface $config)
    {
        $configId = $config->getId();
        if (!$configId instanceof EntityConfigId) {
            throw new \InvalidArgumentException(sprintf(
                'Expected an entity config. Config Id: %s.',
                (string)$configId
            ));
        }

        foreach (['label', 'plural_label', 'grid_all_view_label'] as $key) {
            $transKey = $config->get($key);
            if (!$this->hasTrans($transKey)) {
                $missingTranslationKeys[] = $transKey . sprintf(' [Entity: %s]', $configId->getClassName());
            }
        }
    }

    private function addMissingTranslationKeysForField(array &$missingTranslationKeys, ConfigInterface $config)
    {
        $configId = $config->getId();
        if (!$configId instanceof FieldConfigId) {
            throw new \InvalidArgumentException(sprintf(
                'Expected a field config. Config Id: %s.',
                (string)$configId
            ));
        }

        $transKey = $config->get('label');
        if (!$this->hasTrans($transKey)) {
            $missingTranslationKeys[] = $transKey
                . sprintf(' [Entity: %s; Field: %s]', $configId->getClassName(), $configId->getFieldName());
        }
    }

    /**
     * @param array  $missingTranslationKeys
     * @param string $entityClass
     * @param string $associationName
     * @param string $associationTranslatedLabel
     */
    private function addMissingTranslationKeysForUnidirectionalAssociation(
        array &$missingTranslationKeys,
        $entityClass,
        $associationName,
        $associationTranslatedLabel
    ) {
        // label format is "field label (entity label or entity plural label)"
        if (false === strpos($associationTranslatedLabel, 'extend.entity.test')
            && (
                false !== strpos($associationTranslatedLabel, '.label (')
                || $this->endsWith($associationTranslatedLabel, '.entity_label)')
                || $this->endsWith($associationTranslatedLabel, '.entity_plural_label)')
            )
        ) {
            $missingTranslationKeys[] = $associationTranslatedLabel
                . sprintf(' [Entity: %s; Association: %s]', $entityClass, $associationName);
        }
    }

    /**
     * @param array  $missingTranslationKeys
     * @param string $entityClass
     * @param string $fieldName
     * @param string $fieldTranslatedLabel
     */
    private function addMissingTranslationKeysForVirtualField(
        array &$missingTranslationKeys,
        $entityClass,
        $fieldName,
        $fieldTranslatedLabel
    ) {
        if (!$this->isTestLabel($fieldTranslatedLabel)
            && $this->endsWith($fieldTranslatedLabel, '.label')
        ) {
            $missingTranslationKeys[] = $fieldTranslatedLabel
                . sprintf(' [Entity: %s; Virtual Field: %s]', $entityClass, $fieldName);
        }
    }

    /**
     * @param string $transKey
     *
     * @return bool
     */
    private function hasTrans($transKey)
    {
        return $this->isTestLabel($transKey) || $this->translator->hasTrans($transKey);
    }

    /**
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private function endsWith($haystack, $needle)
    {
        return \substr($haystack, -\strlen($needle)) === $needle;
    }

    /**
     * @param string $entityClass
     *
     * @return bool
     */
    private function isTestEntity($entityClass)
    {
        return is_a($entityClass, TestFrameworkEntityInterface::class, true);
    }

    /**
     * @param string $transKey
     *
     * @return bool
     */
    private function isTestLabel($transKey)
    {
        return 0 === strpos($transKey, 'extend.entity.test');
    }
}
