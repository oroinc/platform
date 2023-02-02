<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ExtendEntityConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Adds translations to enum entity in case of their absence
 */
class UpdateEnumEntityTranslations extends AbstractFixture implements VersionedFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Fields of the entity that should have translations
     */
    private const FIELDS = ['label', 'plural_label', 'description'];

    /** @var ConfigManager */
    private $configManager;

    /** @var ExtendEntityConfigProvider */
    private $extendEntityConfigProvider;

    /** @var TranslationManager */
    private $translationManager;

    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $this->configManager = $this->container->get('oro_entity_config.config_manager');
            $this->translationManager = $this->container->get('oro_translation.manager.translation');
            $this->extendEntityConfigProvider =
                $this->container->get('oro_entity_config.provider.extend_entity_config_provider');

            $this->findAndUpdateEnumEntityTranslations();
        }
    }

    private function findAndUpdateEnumEntityTranslations(): void
    {
        $enumConfigProvider = $this->configManager->getProvider('enum');
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs = $this->extendEntityConfigProvider->getExtendEntityConfigs();
        foreach ($entityConfigs as $entityConfig) {
            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                $fieldType = $fieldConfigId->getFieldType();

                if (ExtendScope::STATE_ACTIVE !== $fieldConfig->get('state')) {
                    continue;
                }

                if (ExtendScope::OWNER_SYSTEM === $fieldConfig->get('owner')) {
                    continue;
                }

                if (!\in_array($fieldType, [EnumTypeHelper::TYPE_ENUM, EnumTypeHelper::MULTI_ENUM], false)) {
                    continue;
                }

                $enumFieldConfig = $enumConfigProvider->getConfig(
                    $fieldConfigId->getClassName(),
                    $fieldConfigId->getFieldName()
                );

                $enumCode = $enumFieldConfig->get('enum_code');
                if (!$enumCode) {
                    continue;
                }

                foreach (self::FIELDS as $fieldName) {
                    $translationKey = ExtendHelper::getEnumTranslationKey($fieldName, $enumCode);
                    if (!$this->isTranslationKeyExists($translationKey)) {
                        $this->translationManager->saveTranslation(
                            $translationKey,
                            $translationKey,
                            Translator::DEFAULT_LOCALE,
                            TranslationManager::DEFAULT_DOMAIN,
                            Translation::SCOPE_UI
                        );
                    }
                }
            }
        }

        $this->translationManager->invalidateCache(Translator::DEFAULT_LOCALE);
        $this->translationManager->flush();
    }

    private function isTranslationKeyExists($key): bool
    {
        $translationKey = $this->translationManager->findTranslationKey($key);

        return $translationKey->getId() ?: false;
    }
}
