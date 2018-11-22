<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides detailed information about entities.
 */
class EntityProvider
{
    /** @var ConfigProvider */
    protected $entityConfigProvider;

    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ExclusionProviderInterface */
    protected $exclusionProvider;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * Constructor
     *
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param TranslatorInterface $translator
     * @param FeatureChecker      $featureChecker
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        TranslatorInterface $translator,
        FeatureChecker $featureChecker
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityClassResolver = $entityClassResolver;
        $this->translator = $translator;
        $this->featureChecker = $featureChecker;
    }

    /**
     * Sets exclusion provider
     *
     * @param ExclusionProviderInterface $exclusionProvider
     */
    public function setExclusionProvider(ExclusionProviderInterface $exclusionProvider)
    {
        $this->exclusionProvider = $exclusionProvider;
    }

    /**
     * Returns enabled entities
     *
     * @param bool $sortByPluralLabel If true entities will be sorted by 'plural_label'; otherwise, by 'label'
     * @param bool $applyExclusions   Indicates whether exclusion logic should be applied.
     * @param bool $translate         Flag means that label, plural label should be translated
     *
     * @return array of entities sorted by entity label
     *                                .    'name'          - entity full class name
     *                                .    'label'         - entity label
     *                                .    'plural_label'  - entity plural label
     *                                .    'icon'          - an icon associated with an entity
     */
    public function getEntities(
        $sortByPluralLabel = true,
        $applyExclusions = true,
        $translate = true
    ) {
        $result = [];
        $this->addEntities($result, $applyExclusions, $translate);
        $this->sortEntities($result, $sortByPluralLabel ? 'plural_label' : 'label');

        return $result;
    }

    /**
     * Returns an entity even if it is disabled
     *
     * @param string $entityName Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $translate  Flag means that label, plural label should be translated
     *
     * @return array contains entity details:
     *                           .    'name'          - entity full class name
     *                           .    'label'         - entity label
     *                           .    'plural_label'  - entity plural label
     *                           .    'icon'          - an icon associated with an entity
     */
    public function getEntity($entityName, $translate = true)
    {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        $config = $this->entityConfigProvider->getConfig($className);
        $result = [];
        $this->addEntity(
            $result,
            $config->getId()->getClassName(),
            $config->get('label'),
            $config->get('plural_label'),
            $config->get('icon'),
            $translate
        );

        return reset($result);
    }

    /**
     * Returns an entity if it is enabled
     *
     * @param string $entityName      Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool   $applyExclusions Indicates whether exclusion logic should be applied.
     * @param bool   $translate       Flag means that label, plural label should be translated
     *
     * @return array contains entity details:
     *                                .    'name'          - entity full class name
     *                                .    'label'         - entity label
     *                                .    'plural_label'  - entity plural label
     *                                .    'icon'          - an icon associated with an entity
     */
    public function getEnabledEntity($entityName, $applyExclusions = true, $translate = true)
    {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        $config = $this->entityConfigProvider->getConfig($className);
        if (!$this->isEntityEnabled($config, $applyExclusions)) {
            throw new RuntimeException(sprintf('Entity "%s" is not enabled', $className));
        }

        $result = [];
        $this->addEntity(
            $result,
            $config->getId()->getClassName(),
            $config->get('label'),
            $config->get('plural_label'),
            $config->get('icon'),
            $translate
        );

        return reset($result);
    }

    /**
     * Adds entities to $result
     *
     * @param array $result
     * @param bool  $applyExclusions
     * @param bool  $translate
     */
    protected function addEntities(array &$result, $applyExclusions, $translate)
    {
        // only configurable entities are supported
        $entityConfigs = $this->entityConfigProvider->getConfigs();

        foreach ($entityConfigs as $entityConfig) {
            if (!$this->isEntityEnabled($entityConfig, $applyExclusions)) {
                continue;
            }

            $this->addEntity(
                $result,
                $entityConfig->getId()->getClassName(),
                $entityConfig->get('label'),
                $entityConfig->get('plural_label'),
                $entityConfig->get('icon'),
                $translate
            );
        }
    }

    /**
     * @param ConfigInterface $entityConfig
     * @param bool            $applyExclusions
     *
     * @return bool
     */
    protected function isEntityEnabled(ConfigInterface $entityConfig, $applyExclusions)
    {
        $entityConfigId = $entityConfig->getId();

        $isStateCorrect = $this->extendConfigProvider
            ->getConfigById($entityConfigId)
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
        if (!$isStateCorrect) {
            return false;
        }

        $className = $entityConfigId->getClassName();
        if ($applyExclusions && $this->isIgnoredEntity($className)) {
            return false;
        }

        return $this->featureChecker->isResourceEnabled($className, 'entities');
    }

    /**
     * Checks if the given entity should be ignored
     *
     * @param string $className
     *
     * @return bool
     */
    public function isIgnoredEntity($className)
    {
        return $this->exclusionProvider->isIgnoredEntity($className);
    }

    /**
     * Adds an entity to $result
     *
     * @param array  $result
     * @param string $name
     * @param string $label
     * @param string $pluralLabel
     * @param string $icon
     * @param bool   $translate
     */
    protected function addEntity(array &$result, $name, $label, $pluralLabel, $icon, $translate)
    {
        $result[] = [
            'name'         => $name,
            'label'        => $translate ? $this->translator->trans($label) : $label,
            'plural_label' => $translate ? $this->translator->trans($pluralLabel) : $pluralLabel,
            'icon'         => $icon
        ];
    }

    /**
     * Sorts entities by a value of the given attribute
     *
     * @param array  $entities
     * @param string $attrName
     */
    protected function sortEntities(array &$entities, $attrName)
    {
        usort(
            $entities,
            function ($a, $b) use (&$attrName) {
                return strcasecmp($a[$attrName], $b[$attrName]);
            }
        );
    }
}
