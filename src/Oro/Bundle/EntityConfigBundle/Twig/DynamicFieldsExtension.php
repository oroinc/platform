<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\EntityExtendBundle\Extend\ExtendManager;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\OptionSetRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\OptionSetRelationRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class DynamicFieldsExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config_fields';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigProviderInterface
     */
    protected $extendProvider;

    /**
     * @var ConfigProviderInterface
     */
    protected $entityProvider;

    /**
     * @var ConfigProviderInterface
     */
    protected $viewProvider;

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var string
     */
    protected $entityViewRoute = 'oro_entity_view';

    /**
     * @param ConfigManager $configManager
     * @param ConfigProviderInterface $extendProvider
     * @param ConfigProviderInterface $entityProvider
     * @param ConfigProviderInterface $viewProvider
     * @param DateTimeFormatter $dateTimeFormatter
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        ConfigManager $configManager,
        ConfigProviderInterface $extendProvider,
        ConfigProviderInterface $entityProvider,
        ConfigProviderInterface $viewProvider,
        DateTimeFormatter $dateTimeFormatter,
        UrlGeneratorInterface $router
    ) {
        $this->configManager = $configManager;
        $this->extendProvider = $extendProvider;
        $this->entityProvider = $entityProvider;
        $this->viewProvider = $viewProvider;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->router = $router;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('oro_get_dynamic_fields', array($this, 'getFields')),
        );
    }

    /**
     * @param object $entity
     * @param null|string $entityClass
     * @return array
     */
    public function getFields($entity, $entityClass = null)
    {
        $dynamicRow = array();
        if (null === $entityClass) {
            $entityClass = ClassUtils::getRealClass($entity);
        }

        $fields = $this->extendProvider->filter(array($this, 'filterFields'), $entityClass);

        foreach ($fields as $field) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $field->getId();

            $fieldName = $fieldConfigId->getFieldName();
            $value = $this->propertyAccessor->getValue($entity, $fieldName);

            /** Prepare DateTime field type */
            if ($value instanceof \DateTime) {
                $value = $this->getValueForDateTime($value);
            }

            /** Prepare OptionSet field type */
            if ($fieldConfigId->getFieldType() == 'optionSet') {
                $value = $this->getValueForOptionSet($entity, $fieldConfigId);
            }

            /** Prepare Relation field type */
            if ($value instanceof Collection) {
                $value = $this->getValueForCollection($value, $fieldConfigId);
            }

            $fieldConfig = $this->entityProvider->getConfigById($fieldConfigId);
            $rowKey = $fieldConfig->get('label');
            if (!$rowKey) {
                $rowKey = $fieldName;
            }
            $dynamicRow[$rowKey] = $value;
        }

        return $dynamicRow;
    }

    /**
     * @param \DateTime $value
     * @return string
     */
    protected function getValueForDateTime(\DateTime $value)
    {
        return $this->dateTimeFormatter->formatDate($value);
    }

    /**
     * @param object $entity
     * @param FieldConfigId $fieldConfig
     * @return OptionSetRelation[]
     */
    protected function getValueForOptionSet($entity, FieldConfigId $fieldConfig)
    {
        /** @var OptionSetRelationRepository */
        $optionSetRepository = $this->configManager
            ->getEntityManager()
            ->getRepository(OptionSetRelation::ENTITY_NAME);

        $model = $this->configManager->getConfigFieldModel(
            $fieldConfig->getClassName(),
            $fieldConfig->getFieldName()
        );

        $value = $optionSetRepository->findByFieldId($model->getId(), $entity->getId());
        array_walk(
            $value,
            function (OptionSetRelation &$item) {
                $item = array('title' => $item->getOption()->getLabel());
            }
        );

        $value['values'] = $value;

        return $value;
    }

    /**
     * @param Collection $collection
     * @param ConfigIdInterface $fieldConfig
     * @return array
     */
    protected function getValueForCollection(Collection $collection, ConfigIdInterface $fieldConfig)
    {
        $extendConfig = $this->extendProvider->getConfigById($fieldConfig);
        $titleFieldName = $extendConfig->get('target_title');
        $targetEntity = $extendConfig->get('target_entity');

        /** generate link for related entities collection */
        $route = false;
        $routeParams = false;
        if (class_exists($targetEntity)) {
            /** @var EntityMetadata $metadata */
            $metadata = $this->configManager->getEntityMetadata($targetEntity);
            if ($metadata && $metadata->routeView) {
                $route = $metadata->routeView;
                $routeParams = array(
                    'id' => null
                );
            }

            $relationExtendConfig = $this->extendProvider->getConfig($targetEntity);
            if ($relationExtendConfig->is('owner', ExtendManager::OWNER_CUSTOM)) {
                $route = $this->entityViewRoute;
                $routeParams = array(
                    'entity_id' => str_replace('\\', '_', $targetEntity),
                    'id' => null
                );
            }
        }

        $value = array(
            'route' => $route,
            'route_params' => $routeParams,
            'values' => array()
        );

        /** @var object $item */
        foreach ($collection as $item) {
            $routeParams['id'] = $item->getId();

            $title = array();
            foreach ($titleFieldName as $fieldName) {
                $title[] = $this->propertyAccessor->getValue($item, $fieldName);
            }

            $value['values'][] = array(
                'id' => $item->getId(),
                'link' => $route ? $this->router->generate($route, $routeParams) : false,
                'title' => implode(' ', $title)
            );
        }

        return $value;
    }

    /**
     * @param ConfigInterface $config
     * @return bool
     */
    public function filterFields(ConfigInterface $config)
    {
        $extendConfig = $this->extendProvider->getConfigById($config->getId());
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $extendConfig->getId();

        return
            $config->is('owner', ExtendManager::OWNER_CUSTOM)
            && !$config->is('state', ExtendManager::STATE_NEW)
            && !$config->is('is_deleted')
            && $this->viewProvider->getConfigById($config->getId())->is('is_displayable')
            && !(
                in_array($fieldConfigId->getFieldType(), array('oneToMany', 'manyToOne', 'manyToMany'))
                && $this->extendProvider->getConfig($extendConfig->get('target_entity'))->is('is_deleted', true)
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
