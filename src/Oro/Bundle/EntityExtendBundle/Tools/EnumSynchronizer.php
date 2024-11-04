<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\TranslatableQueryTrait;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\DoctrineUtils\ORM\Walker\TranslatableSqlWalker;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This class contains logic of enum configuration, options and translations synchronization
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EnumSynchronizer
{
    use TranslatableQueryTrait;

    public function __construct(
        protected ConfigManager $configManager,
        protected ManagerRegistry $doctrine,
        protected TranslatorInterface $translator,
        protected ConfigTranslationHelper $translationHelper
    ) {
    }

    /**
     * Synchronizes enum related data for new enums
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function sync(): void
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $enumConfigProvider = $this->configManager->getProvider('enum');
        $entityConfigs = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend') || $entityConfig->is('is_deleted')) {
                continue;
            }

            $fieldConfigs = $extendConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($fieldConfigs as $fieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $fieldConfig->getId();
                if ($fieldConfig->is('is_deleted') || !ExtendHelper::isEnumerableType($fieldConfigId->getFieldType())) {
                    continue;
                }

                $enumFieldConfig = $enumConfigProvider->getConfig(
                    $fieldConfigId->getClassName(),
                    $fieldConfigId->getFieldName()
                );

                $enumCode = $enumFieldConfig->get('enum_code');
                if (!empty($enumCode)) {
                    $enumName = $enumFieldConfig->get('enum_name');
                    $locale = $enumFieldConfig->get('enum_locale');
                    $isPublic = $enumFieldConfig->get('enum_public');
                    $enumOptions = $enumFieldConfig->get('enum_options');

                    if (empty($locale)) {
                        $locale = Translator::DEFAULT_LOCALE;
                    }

                    if (!empty($enumName)) {
                        $this->applyEnumNameTrans($enumCode, $enumName, $locale);
                    }
                    if (!empty($enumOptions)) {
                        $this->applyEnumOptions($enumCode, EnumOption::class, $enumOptions, $locale);
                    }
                    if ($isPublic !== null) {
                        $this->applyEnumEntityOptions($enumFieldConfig, $isPublic, false);
                    }

                    $this->updateEnumFieldConfig($enumFieldConfig);
                }
            }
        }

        $this->configManager->flush();
    }

    public function applyEnumNameTrans(
        string $enumCode,
        ?string $enumName = null,
        string $locale = Translator::DEFAULT_LOCALE
    ): void {
        $labelsToBeUpdated = [];
        $labelKey = ExtendHelper::getEnumTranslationKey('label', $enumCode);
        $pluralLabelKey = ExtendHelper::getEnumTranslationKey('plural_label', $enumCode);
        $descriptionKey = ExtendHelper::getEnumTranslationKey('description', $enumCode);

        // Checks if translations need to be updated for a entity
        if ($enumName === $this->translator->trans($labelKey, [], null, $locale)) {
            return;
        }

        $labelsToBeUpdated[$labelKey] = $enumName ?: $labelKey;
        $labelsToBeUpdated[$pluralLabelKey] = $enumName ?: $pluralLabelKey;
        $labelsToBeUpdated[$descriptionKey] = $enumName ?: $descriptionKey;

        $this->translationHelper->saveTranslations($labelsToBeUpdated);
    }

    public function applyEnumEntityOptions(
        ConfigInterface $config,
        bool $isPublic,
        bool $doFlush = true
    ): void {
        if (!$config->is('enum_public', $isPublic)) {
            $config->set('enum_public', $isPublic);
            $this->configManager->persist($config);
            if ($doFlush) {
                $this->configManager->flush();
            }
        }
    }

    public function applyEnumOptions(
        string $enumCode,
        string $enumOptionClassName,
        array $options,
        string $locale
    ): void {
        if (empty($enumOptionClassName)) {
            throw new \InvalidArgumentException('$enumOptionClassName must not be empty.');
        }
        if (empty($locale)) {
            throw new \InvalidArgumentException('$locale must not be empty.');
        }
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($enumOptionClassName);
        $em->beginTransaction();

        try {
            /** @var EnumOptionRepository $enumRepo */
            $enumRepo = $em->getRepository($enumOptionClassName);

            /** @var EnumOptionInterface[] $values */
            $values = $enumRepo->createQueryBuilder('o')
                ->andWhere('o.enumCode = :enumCode')
                ->setParameter('enumCode', $enumCode)
                ->getQuery()
                ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale)
                ->getResult();

            $changes = $this->processOptions($enumCode, $values, $options, $em, $enumRepo);
            if (!empty($changes)) {
                foreach ($changes as $value) {
                    $value->setLocale($locale);
                }
                $em->flush($changes);
                $this->translationHelper->invalidateCache($locale);
            }
            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            throw $exception;
        }
    }

    protected function getTranslationRepository(): TranslationRepository
    {
        return $this->doctrine->getManagerForClass(Translation::class)
            ->getRepository(Translation::class);
    }

    protected function generateEnumOptionId(string $enumCode, string $name, array $existingIds): string
    {
        $internalId = ExtendHelper::buildEnumInternalId($name);
        $id = ExtendHelper::buildEnumOptionId($enumCode, $internalId);

        $counter = 1;
        $originalId = $id;
        while (in_array($id, $existingIds, true)) {
            $id = sprintf('%s_%s', $originalId, $counter);
            $counter++;
        }

        return $id;
    }

    public function getEnumOptions(string $enumCode, string $enumOptionsClassName): mixed
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($enumOptionsClassName);
        $enumRepository = $em->getRepository($enumOptionsClassName);
        $query = $enumRepository->createQueryBuilder('e')
            ->select('e.id, e.enumCode, e.priority, e.default as is_default')
            ->where('e.enumCode = :enumCode')
            ->setParameter('enumCode', $enumCode)
            ->orderBy('e.priority')
            ->getQuery()
            ->setHint(
                Query::HINT_CUSTOM_OUTPUT_WALKER,
                TranslatableSqlWalker::class
            );

        $this->addTranslatableLocaleHint($query, $em);
        $result = $query->getArrayResult();
        foreach ($result as &$item) {
            $item['label'] = $this->translator->trans(
                ExtendHelper::buildEnumOptionTranslationKey($item['id']),
            );
        }

        return $result;
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
            if ((string)$id === (string)$opt['id']) {
                return $optKey;
            }
        }

        return null;
    }

    protected function setEnumOptionProperties(EnumOptionInterface $value, array $option): bool
    {
        $hasChanges = false;
        if ($value->getName() != $option['label']) {
            $value->setName($option['label']);
            // translation value will be updated
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

        $attributes = ['enum_name', 'enum_locale', 'enum_options'];
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

    protected function processOptions(
        string $enumCode,
        array $values,
        array $options,
        EntityManager $em,
        EnumOptionRepository $enumRepo
    ): array {
        $this->fillOptionIds($values, $options, $enumCode);

        /** @var EnumOptionInterface[] $changes */
        $changes = [];
        /** @var EnumOptionInterface[] $removes */
        $removes = [];
        foreach ($values as $value) {
            $optionKey = $this->getEnumOptionKey($value->getId(), $options);
            // If generated id is equal to existing one and generated was prefixed
            // Then remove existing value and create new one
            if ($optionKey !== null && !empty($options[$optionKey]['generated'])) {
                $originalId = $this->generateEnumOptionId($enumCode, $options[$optionKey]['label'], []);
                if ($originalId !== $options[$optionKey]['id']) {
                    $optionKey = null;
                }
            }

            // If existing value was found by option id or label if id was empty - update value
            if ($optionKey !== null) {
                if ($this->setEnumOptionProperties($value, $options[$optionKey])) {
                    $changes[] = $value;
                }
                unset($options[$optionKey]);
            } else {
                $em->remove($value);
                $removes[] = $value;
            }
        }
        if ($removes) {
            $em->flush($removes);
        }

        foreach ($options as $option) {
            // Create new values for options that had no matching value
            $value = $enumRepo->createEnumOption(
                $enumCode,
                ExtendHelper::getEnumInternalId((string)$option['id']),
                (string)$option['label'],
                $option['priority'],
                $option['is_default'],
            );
            $em->persist($value);
            $changes[] = $value;
        }

        return $changes;
    }

    protected function getIdByExistingValues(array $values, array $option): ?string
    {
        foreach ($values as $value) {
            if ($value->getName() === $option['label']) {
                return $value->getId();
            }
        }

        return null;
    }

    public function fillOptionIds(array $values, array &$options, ?string $enumCode = null): void
    {
        $ids = array_map(function (EnumOptionInterface $value) {
            return $value->getId();
        }, $values);
        $startPriority = 1;
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
            if (!isset($option['priority'])) {
                $option['priority'] = $startPriority;
                $startPriority++;
            }
        }
        unset($option);

        // Generate ids for options without ids
        foreach ($options as &$option) {
            if ($this->isEmptyOption($option, 'id')) {
                $id = $this->generateEnumOptionId($enumCode ?? $option['enumCode'], $option['label'], $ids);
                $option['generated'] = true;
                $option['id'] = $id;
                $ids[] = $option['id'];
            }
        }
    }

    protected function isEmptyOption(array $option, string $key): bool
    {
        return $option['label'] !== '' && $option['label'] !== null &&
            (!array_key_exists($key, $option) || $option[$key] === null || $option[$key] === '');
    }
}
