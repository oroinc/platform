<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\Translation\TranslatorInterface;

use Gedmo\Translatable\TranslatableListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EnumSynchronizer
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DynamicTranslationMetadataCache */
    protected $dbTranslationMetadataCache;

    /**
     * @param ConfigManager                   $configManager
     * @param ManagerRegistry                 $doctrine
     * @param TranslatorInterface             $translator
     * @param DynamicTranslationMetadataCache $dbTranslationMetadataCache
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        DynamicTranslationMetadataCache $dbTranslationMetadataCache
    ) {
        $this->configManager              = $configManager;
        $this->doctrine                   = $doctrine;
        $this->translator                 = $translator;
        $this->dbTranslationMetadataCache = $dbTranslationMetadataCache;
    }

    /**
     * Synchronizes enum related data for new enums
     */
    public function sync()
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $entityConfigs        = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend') || $entityConfig->is('is_deleted')) {
                continue;
            }

            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if ($fieldConfig->is('is_deleted')
                    || !in_array($fieldConfigId->getFieldType(), ['enum', 'multiEnum'], true)
                ) {
                    continue;
                }

                $enumFieldConfig = $enumConfigProvider->getConfig(
                    $fieldConfigId->getClassName(),
                    $fieldConfigId->getFieldName()
                );

                $enumCode = $enumFieldConfig->get('enum_code');
                if (!empty($enumCode)) {
                    $enumValueClassName = $fieldConfig->get('target_entity');
                    $enumName           = $enumFieldConfig->get('enum_name');
                    $locale             = $enumFieldConfig->get('enum_locale');
                    $isPublic           = $enumFieldConfig->get('enum_public');
                    $enumOptions        = $enumFieldConfig->get('enum_options');

                    if (empty($locale)) {
                        $locale = Translation::DEFAULT_LOCALE;
                    }

                    if (!empty($enumName)) {
                        $this->applyEnumNameTrans($enumCode, $enumName, $locale);
                    }
                    if (!empty($enumOptions)) {
                        $this->applyEnumOptions($enumValueClassName, $enumOptions, $locale);
                    }
                    if ($isPublic !== null) {
                        $this->applyEnumEntityOptions($enumValueClassName, $isPublic, false);
                    }

                    $this->updateEnumFieldConfig($enumFieldConfig);
                }
            }
        }

        $this->configManager->flush();
    }

    /**
     * @param string $enumCode
     * @param string $enumName
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     */
    public function applyEnumNameTrans($enumCode, $enumName, $locale)
    {
        if (strlen($enumCode) === 0) {
            throw new \InvalidArgumentException('$enumCode must not be empty.');
        }
        if (strlen($enumName) === 0) {
            throw new \InvalidArgumentException('$enumName must not be empty.');
        }
        if (empty($locale)) {
            throw new \InvalidArgumentException('$locale must not be empty.');
        }

        // add translations for an entity will be used to store enum values
        $labelsToBeUpdated = [];
        $labelKey          = ExtendHelper::getEnumTranslationKey('label', $enumCode);
        $pluralLabelKey    = ExtendHelper::getEnumTranslationKey('plural_label', $enumCode);
        $descriptionKey    = ExtendHelper::getEnumTranslationKey('description', $enumCode);
        $currentLabelTrans = $this->translator->trans($labelKey, [], null, $locale);
        if ($currentLabelTrans === $labelKey) {
            // labels initialization
            $labelsToBeUpdated[$labelKey]       = $enumName;
            $labelsToBeUpdated[$pluralLabelKey] = $enumName;
            if ($locale === Translation::DEFAULT_LOCALE) {
                // set empty description only for default locale
                $labelsToBeUpdated[$descriptionKey] = '';
            }
        } elseif ($enumName != $currentLabelTrans) {
            // update existing labels
            $labelsToBeUpdated[$labelKey]       = $enumName;
            $labelsToBeUpdated[$pluralLabelKey] = $enumName;
        }
        if (!empty($labelsToBeUpdated)) {
            /** @var EntityManager $em */
            $em = $this->doctrine->getManagerForClass(Translation::ENTITY_NAME);
            /** @var TranslationRepository $translationRepo */
            $translationRepo = $em->getRepository(Translation::ENTITY_NAME);
            $transValues     = [];
            foreach ($labelsToBeUpdated as $labelKey => $labelText) {
                // save into translation table
                $transValues[] = $translationRepo->saveValue(
                    $labelKey,
                    $labelText,
                    $locale,
                    TranslationRepository::DEFAULT_DOMAIN,
                    Translation::SCOPE_UI
                );
            }
            // flush translations to db
            $em->flush($transValues);
            // mark translation cache dirty
            $this->dbTranslationMetadataCache->updateTimestamp($locale);
        }
    }

    /**
     * @param string $enumValueClassName
     * @param bool   $isPublic
     * @param bool   $doFlush
     *
     * @throws \InvalidArgumentException
     */
    public function applyEnumEntityOptions($enumValueClassName, $isPublic, $doFlush = true)
    {
        if (empty($enumValueClassName)) {
            throw new \InvalidArgumentException('$enumValueClassName must not be empty.');
        }

        $enumConfigProvider = $this->configManager->getProvider('enum');
        $enumEntityConfig   = $enumConfigProvider->getConfig($enumValueClassName);

        if (!$enumEntityConfig->is('public', $isPublic)) {
            $enumEntityConfig->set('public', $isPublic);
            $this->configManager->persist($enumEntityConfig);
            if ($doFlush) {
                $this->configManager->flush();
            }
        }
    }

    /**
     * @param string $enumValueClassName
     * @param array  $options
     * @param string $locale
     *
     * @throws \InvalidArgumentException
     */
    public function applyEnumOptions($enumValueClassName, array $options, $locale)
    {
        if (empty($enumValueClassName)) {
            throw new \InvalidArgumentException('$enumValueClassName must not be empty.');
        }
        if (empty($locale)) {
            throw new \InvalidArgumentException('$locale must not be empty.');
        }

        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($enumValueClassName);
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $em->getRepository($enumValueClassName);

        /** @var AbstractEnumValue[] $values */
        $values = $enumRepo->createQueryBuilder('o')
            ->getQuery()
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
            ->getResult();

        $ids = [];
        /** @var AbstractEnumValue[] $changes */
        $changes = [];
        foreach ($values as $value) {
            $id        = $value->getId();
            $optionKey = $this->getEnumOptionKey($id, $options);
            if ($optionKey !== null) {
                $ids[] = $id;
                if ($this->setEnumValueProperties($value, $options[$optionKey])) {
                    $changes[] = $value;
                }
                unset($options[$optionKey]);
            } else {
                $em->remove($value);
                $changes[] = $value;
            }
        }

        foreach ($options as $option) {
            $id    = $this->generateEnumValueId($option['label'], $ids);
            $ids[] = $id;
            $value = $enumRepo->createEnumValue(
                $option['label'],
                $option['priority'],
                $option['is_default'],
                $id
            );
            $em->persist($value);
            $changes[] = $value;
        }

        if (!empty($changes)) {
            if ($locale !== Translation::DEFAULT_LOCALE) {
                foreach ($changes as $value) {
                    $value->setLocale($locale);
                }
            }
            $em->flush($changes);
            // mark translation cache dirty
            $this->dbTranslationMetadataCache->updateTimestamp($locale);
        }
    }

    /**
     * @param string   $name
     * @param string[] $existingIds
     *
     * @return string
     */
    protected function generateEnumValueId($name, array $existingIds)
    {
        $id = ExtendHelper::buildEnumValueId($name);
        if (in_array($id, $existingIds, true)) {
            $prefix  = substr($id, 0, ExtendHelper::MAX_ENUM_VALUE_ID_LENGTH - 6) . '_';
            $counter = 1;
            $id = $prefix . $counter;
            while (in_array($id, $existingIds, true)) {
                $counter++;
                $id = $prefix . $counter;
            }
        }

        return $id;
    }

    /**
     * @param string $enumValueClassName
     *
     * @return array
     */
    public function getEnumOptions($enumValueClassName)
    {
        /** @var EntityManager $em */
        $em       = $this->doctrine->getManagerForClass($enumValueClassName);
        $enumRepo = $em->getRepository($enumValueClassName);

        return $enumRepo->createQueryBuilder('e')
            ->select('e.id, e.priority, e.name as label, e.default as is_default')
            ->orderBy('e.priority')
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
            )
            ->getArrayResult();
    }

    /**
     * @param string $id
     * @param array  $options
     *
     * @return int|null
     */
    protected function getEnumOptionKey($id, array $options)
    {
        foreach ($options as $optKey => $opt) {
            if ($id == $opt['id']) {
                return $optKey;
            }
        }

        return null;
    }

    /**
     * @param AbstractEnumValue $value
     * @param array             $option
     *
     * @return bool
     */
    protected function setEnumValueProperties(AbstractEnumValue $value, array $option)
    {
        $hasChanges = false;

        if ($value->getName() != $option['label']) {
            $value->setName($option['label']);
            $hasChanges = true;
        }
        if ($value->getPriority() != $option['priority']) {
            $value->setPriority($option['priority']);
            $hasChanges = true;
        }
        if ($value->isDefault() != $option['is_default']) {
            $value->setDefault($option['is_default']);
            $hasChanges = true;
        }

        return $hasChanges;
    }

    /**
     * @param ConfigInterface $enumFieldConfig
     *
     * @return bool
     */
    protected function updateEnumFieldConfig(ConfigInterface $enumFieldConfig)
    {
        $hasChanges = false;

        $attributes = ['enum_name', 'enum_locale', 'enum_public', 'enum_options'];
        foreach ($attributes as $code) {
            if ($enumFieldConfig->get($code) !== null) {
                $enumFieldConfig->remove($code);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $this->configManager->persist($enumFieldConfig);
        }

        return $hasChanges;
    }
}
