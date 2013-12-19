<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Symfony\Component\Translation\Translator;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityProvider
{
    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * Constructor
     *
     * @param ConfigProvider $entityConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param Translator $translator
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        EntityClassResolver $entityClassResolver,
        Translator $translator
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->entityClassResolver  = $entityClassResolver;
        $this->translator           = $translator;
    }

    /**
     * Returns entities
     *
     * @param bool $sortByPluralLabel If true entities will be sorted by 'plural_label'; otherwise, by 'label'
     * @param bool $translate         Flag means that label, plural label should be translated
     * @return array of entities sorted by entity label
     *                                .    'name'          - entity full class name
     *                                .    'label'         - entity label
     *                                .    'plural_label'  - entity plural label
     *                                .    'icon'          - an icon associated with an entity
     */
    public function getEntities($sortByPluralLabel = true, $translate = true)
    {
        $result = array();
        $this->addEntities($result, $translate);
        $this->sortEntities($result, $sortByPluralLabel ? 'plural_label' : 'label');

        return $result;
    }

    /**
     * Returns entity
     *
     * @param string $entityName Entity name. Can be full class name or short form: Bundle:Entity.
     * @param bool $translate    Flag means that label, plural lable should be translated
     * @return array contains entity details:
     *                           .    'name'          - entity full class name
     *                           .    'label'         - entity label
     *                           .    'plural_label'  - entity plural label
     *                           .    'icon'          - an icon associated with an entity
     */
    public function getEntity($entityName, $translate = true)
    {
        $className = $this->entityClassResolver->getEntityClass($entityName);
        $config    = $this->entityConfigProvider->getConfig($className);
        $result    = array();
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
     * @param bool $translate
     */
    protected function addEntities(array &$result, $translate)
    {
        // only configurable entities are supported
        $configs = $this->entityConfigProvider->getConfigs();
        foreach ($configs as $config) {
            $this->addEntity(
                $result,
                $config->getId()->getClassName(),
                $config->get('label'),
                $config->get('plural_label'),
                $config->get('icon'),
                $translate
            );
        }
    }

    /**
     * Adds an entity to $result
     *
     * @param array $result
     * @param string $name
     * @param string $label
     * @param string $pluralLabel
     * @param string $icon
     * @param bool $translate
     */
    protected function addEntity(array &$result, $name, $label, $pluralLabel, $icon, $translate)
    {
        $result[] = array(
            'name'         => $name,
            'label'        => $translate ? $this->translator->trans($label) : $label,
            'plural_label' => $translate ? $this->translator->trans($pluralLabel) : $pluralLabel,
            'icon'         => $icon
        );
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
