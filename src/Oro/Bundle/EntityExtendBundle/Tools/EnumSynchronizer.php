<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * This class contains logic of enum configuration, options and translations synchronization
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EnumSynchronizer
{
    use TranslatableQueryTrait;

    /** @var ConfigManager */
    protected $configManager;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigTranslationHelper */
    protected $translationHelper;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $doctrine
     * @param TranslatorInterface $translator
     * @param ConfigTranslationHelper $translationHelper
     */
    public function __construct(
        ConfigManager $configManager,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        ConfigTranslationHelper $translationHelper
    ) {
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->translationHelper = $translationHelper;
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
                        $locale = Translator::DEFAULT_LOCALE;
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
            if ($locale === Translator::DEFAULT_LOCALE) {
                // set empty description only for default locale
                $labelsToBeUpdated[$descriptionKey] = '';
            }
        } elseif ($enumName != $currentLabelTrans) {
            // update existing labels
            $labelsToBeUpdated[$labelKey]       = $enumName;
            $labelsToBeUpdated[$pluralLabelKey] = $enumName;
        }

        $this->translationHelper->saveTranslations($labelsToBeUpdated);
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
        $em->beginTransaction();

        try {
            /** @var EnumValueRepository $enumRepo */
            $enumRepo = $em->getRepository($enumValueClassName);

            /** @var AbstractEnumValue[] $values */
            $values = $enumRepo->createQueryBuilder('o')
                ->getQuery()
                ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
                ->getResult();

            $changes = $this->processValues($values, $options, $em, $enumRepo);

            if (!empty($changes)) {
                if ($locale !== Translator::DEFAULT_LOCALE) {
                    foreach ($changes as $value) {
                        $value->setLocale($locale);
                    }
                }
                $em->flush($changes);
                // mark translation cache dirty
                $this->translationHelper->invalidateCache($locale);
            }
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
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
        $query = $enumRepo->createQueryBuilder('e')
            ->select('e.id, e.priority, e.name as label, e.default as is_default')
            ->orderBy('e.priority')
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                TranslationWalker::class
            );

        $this->addTranslatableLocaleHint($query, $em);

        return $query->getArrayResult();
    }

    /**
     * @param string $id
     * @param array $options
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

    /**
     * @param array|AbstractEnumValue[] $values
     * @param array $options
     * @param EntityManager $em
     * @param EnumValueRepository $enumRepo
     *
     * @return array|AbstractEnumValue[]
     */
    protected function processValues(array $values, array $options, EntityManager $em, EnumValueRepository $enumRepo)
    {
        $this->fillOptionIds($values, $options);

        /** @var AbstractEnumValue[] $changes */
        $changes = [];
        /** @var AbstractEnumValue[] $removes */
        $removes = [];
        foreach ($values as $value) {
            $optionKey = $this->getEnumOptionKey($value->getId(), $options);
            // If generated id is equal to existing one and generated was prefixed
            // Then remove existing value and create new one
            if ($optionKey !== null && !empty($options[$optionKey]['generated'])) {
                $originalId = $this->generateEnumValueId($options[$optionKey]['label'], []);
                if ($originalId !== $options[$optionKey]['id']) {
                    $optionKey = null;
                }
            }

            // If existing value was found by option id or label if id was empty - update value
            if ($optionKey !== null) {
                if ($this->setEnumValueProperties($value, $options[$optionKey])) {
                    $changes[] = $value;
                }
                unset($options[$optionKey]);
            } else {
                // If there is no matching option for existing value - remove value
                $em->remove($value);
                $removes[] = $value;
            }
        }
        if ($removes) {
            $em->flush($removes);
        }

        foreach ($options as $option) {
            // Create new values for options that had no matching value
            $value = $enumRepo->createEnumValue(
                $option['label'],
                $option['priority'],
                $option['is_default'],
                $option['id']
            );
            $em->persist($value);
            $changes[] = $value;
        }

        return $changes;
    }

    /**
     * @param array|AbstractEnumValue[] $values
     * @param array $option
     * @return null|string
     */
    protected function getIdByExistingValues(array $values, array $option)
    {
        foreach ($values as $value) {
            if ($value->getName() === $option['label']) {
                return $value->getId();
            }
        }

        return null;
    }

    /**
     * @param array $values
     * @param array $options
     */
    protected function fillOptionIds(array $values, array &$options)
    {
        $ids = array_map(function (AbstractEnumValue $value) {
            return $value->getId();
        }, $values);
        // Fill existing ids by given option ids or by value ids if labels are equal
        foreach ($options as &$option) {
            if ($this->isEmptyOption($option, 'id')) {
                $id = $this->getIdByExistingValues($values, $option);
                if ($id) {
                    $option['id'] = $id;
                    $ids[] = $option['id'];
                }
            } else {
                $ids[] = $option['id'];
            }
        }
        unset($option);

        // Generate ids for options without ids
        foreach ($options as &$option) {
            if ($this->isEmptyOption($option, 'id')) {
                $id = $this->generateEnumValueId($option['label'], $ids);
                $option['generated'] = true;
                $option['id'] = $id;
                $ids[] = $option['id'];
            }
        }
    }

    /**
     * @param array $option
     * @param string $key
     * @return bool
     */
    protected function isEmptyOption(array $option, $key): bool
    {
        return !array_key_exists($key, $option) || $option[$key] === null || $option[$key] === '';
    }
}
