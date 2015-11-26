<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBag;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigExtra;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class RestApiDocHandler implements HandlerInterface
{
    const ID_DESCRIPTION      = 'The identifier of an entity';
    const VERSION_DESCRIPTION = 'The API version';
    const FORMAT_DESCRIPTION  = 'The response format';

    protected $templates = [
        'get'      => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => ConfigUtil::LABEL,
            'documentation_key'    => ConfigUtil::DESCRIPTION
        ],
        'get_list' => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get a list of {class}',
            'get_name_method'      => 'getEntityClassPluralName',
            'description_key'      => ConfigUtil::PLURAL_LABEL,
            'documentation_key'    => ConfigUtil::DESCRIPTION
        ],
    ];

    /** @var ActionProcessorBag */
    protected $processorBag;

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ActionProcessorBag               $processorBag
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param EntityAliasResolver              $entityAliasResolver
     * @param DoctrineHelper                   $doctrineHelper
     * @param ValueNormalizer                  $valueNormalizer
     */
    public function __construct(
        ActionProcessorBag $processorBag,
        EntityClassNameProviderInterface $entityClassNameProvider,
        EntityAliasResolver $entityAliasResolver,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->processorBag            = $processorBag;
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->entityAliasResolver     = $entityAliasResolver;
        $this->doctrineHelper          = $doctrineHelper;
        $this->valueNormalizer         = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption('group') !== RestApiRouteOptionsResolver::ROUTE_GROUP) {
            return;
        }
        $action = $route->getDefault('_action');
        if (empty($action)) {
            return;
        }

        $entityClass = $this->getEntityClass($route);
        $config      = $this->getConfig($action, $entityClass);
        $this->setDescription($annotation, $action, (array)$config->getConfig(), $entityClass);
        if ($entityClass && $this->hasAttribute($route, RestApiRouteOptionsResolver::ID_PLACEHOLDER)) {
            $this->addIdRequirement(
                $annotation,
                $entityClass,
                $route->getRequirement(RestApiRouteOptionsResolver::ID_ATTRIBUTE)
            );
        }
        $versionRequirement = $route->getRequirement(RestApiRouteOptionsResolver::VERSION_ATTRIBUTE);
        if ($versionRequirement) {
            $this->addVersionRequirement($annotation, $versionRequirement);
        }
        $formatRequirement = $route->getRequirement(RestApiRouteOptionsResolver::FORMAT_ATTRIBUTE);
        if ($formatRequirement) {
            $this->addFormatRequirement($annotation, $formatRequirement);
        }

        if ($config->hasConfigSection(ConfigUtil::FILTERS) && method_exists($config, 'getFilters')) {
            $this->addFilters($annotation, $config->getFilters());
        }
    }

    /**
     * @param Route $route
     *
     * @return string|null
     */
    protected function getEntityClass(Route $route)
    {
        $pluralAlias = $route->getDefault(RestApiRouteOptionsResolver::ENTITY_ATTRIBUTE);

        return $pluralAlias
            ? $this->entityAliasResolver->getClassByPluralAlias($pluralAlias)
            : null;
    }

    /**
     * @param string      $action
     * @param string|null $entityClass
     *
     * @return Context
     */
    protected function getConfig($action, $entityClass)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->removeConfigSection(ConfigUtil::SORTERS);
        $context->addConfigExtra(ConfigExtra::DESCRIPTIONS);
        $context->setRequestType(RequestType::REST);
        $context->setLastGroup('initialize');
        if ($entityClass) {
            $context->setClassName($entityClass);
        }
        $processor->process($context);

        return $context;
    }

    /**
     * @param ApiDoc      $annotation
     * @param string      $action
     * @param array       $config
     * @param string|null $entityClass
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function setDescription(ApiDoc $annotation, $action, array $config, $entityClass = null)
    {
        $templates  = $this->templates[$action];
        $entityName = false;

        // set description
        $description = null;
        if (!empty($config[$templates['description_key']])) {
            $description = $config[$templates['description_key']];
        }
        if ($description) {
            $description = strtr($templates['description'], ['{name}' => $description]);
        } elseif ($entityClass) {
            $entityName  = $this->entityClassNameProvider->{$templates['get_name_method']}($entityClass);
            $description = $entityName
                ? strtr($templates['description'], ['{name}' => $entityName])
                : strtr($templates['fallback_description'], ['{class}' => $entityClass]);
        }
        if ($description) {
            $annotation->setDescription($description);
        }

        // set documentation
        $documentation = null;
        if (!empty($config[$templates['documentation_key']])) {
            $documentation = $config[$templates['documentation_key']];
        }
        if (!$documentation && $entityClass) {
            if (false === $entityName) {
                $documentation = $this->entityClassNameProvider->{$templates['get_name_method']}($entityClass);
            }
            if ($entityName) {
                $documentation = $entityName;
            }
        }
        if ($documentation) {
            $annotation->setDocumentation($documentation);
        }
    }

    /**
     * @param ApiDoc $annotation
     * @param string $requirement
     */
    protected function addVersionRequirement(ApiDoc $annotation, $requirement)
    {
        $annotation->addRequirement(
            RestApiRouteOptionsResolver::VERSION_ATTRIBUTE,
            [
                'dataType'    => ApiDocDataTypeConverter::convertToApiDocDataType(DataType::STRING),
                'requirement' => $requirement,
                'description' => self::VERSION_DESCRIPTION
            ]
        );
    }

    /**
     * @param ApiDoc $annotation
     * @param string $requirement
     */
    protected function addFormatRequirement(ApiDoc $annotation, $requirement)
    {
        $annotation->addRequirement(
            RestApiRouteOptionsResolver::FORMAT_ATTRIBUTE,
            [
                'dataType'    => ApiDocDataTypeConverter::convertToApiDocDataType(DataType::STRING),
                'requirement' => $requirement,
                'description' => self::FORMAT_DESCRIPTION
            ]
        );
    }

    /**
     * @param ApiDoc $annotation
     * @param string $entityClass
     * @param string $requirement
     */
    protected function addIdRequirement(ApiDoc $annotation, $entityClass, $requirement)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $idFields = $metadata->getIdentifierFieldNames();
        $dataType = count($idFields) === 1
            ? $metadata->getTypeOfField(reset($idFields))
            : DataType::STRING;

        $annotation->addRequirement(
            RestApiRouteOptionsResolver::ID_ATTRIBUTE,
            [
                'dataType'    => ApiDocDataTypeConverter::convertToApiDocDataType($dataType),
                'requirement' => $requirement,
                'description' => self::ID_DESCRIPTION
            ]
        );
    }

    /**
     * @param ApiDoc           $annotation
     * @param FilterCollection $filters
     */
    protected function addFilters(ApiDoc $annotation, FilterCollection $filters)
    {
        foreach ($filters as $key => $filter) {
            if ($filter instanceof StandaloneFilter) {
                $options = [
                    'description' => $filter->getDescription(),
                    'requirement' => $this->valueNormalizer->getRequirement(
                        $filter->getDataType(),
                        RequestType::REST,
                        $filter->isArrayAllowed() ? RestRequest::ARRAY_DELIMITER : null
                    )
                ];
                $default = $filter->getDefaultValueString();
                if (!empty($default)) {
                    $options['default'] = $default;
                }
                $annotation->addFilter($key, $options);
            }
        }
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    protected function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }
}
