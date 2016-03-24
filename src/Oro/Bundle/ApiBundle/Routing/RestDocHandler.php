<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class RestDocHandler implements HandlerInterface
{
    const ID_DESCRIPTION     = 'The identifier of an entity';
    const FORMAT_DESCRIPTION = 'The response format';

    protected $templates = [
        'get'      => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => EntityDefinitionConfig::LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
        'get_list' => [
            'description'          => 'Get {name}',
            'fallback_description' => 'Get a list of {class}',
            'get_name_method'      => 'getEntityClassPluralName',
            'description_key'      => EntityDefinitionConfig::PLURAL_LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
    ];

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RequestType */
    protected $requestType;

    /**
     * @param RestDocViewDetector              $docViewDetector
     * @param ActionProcessorBagInterface      $processorBag
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     * @param EntityAliasResolver              $entityAliasResolver
     * @param DoctrineHelper                   $doctrineHelper
     * @param ValueNormalizer                  $valueNormalizer
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        EntityClassNameProviderInterface $entityClassNameProvider,
        EntityAliasResolver $entityAliasResolver,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->docViewDetector         = $docViewDetector;
        $this->processorBag            = $processorBag;
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->entityAliasResolver     = $entityAliasResolver;
        $this->doctrineHelper          = $doctrineHelper;
        $this->valueNormalizer         = $valueNormalizer;
        $this->requestType             = new RequestType([RequestType::REST, RequestType::JSON_API]);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption('group') !== RestRouteOptionsResolver::ROUTE_GROUP) {
            return;
        }
        $action = $route->getDefault('_action');
        if (empty($action)) {
            return;
        }

        $entityClass = $this->getEntityClass($route);
        if ($entityClass) {
            $config = $this->getConfig($action, $entityClass);
            $this->setDescription($annotation, $action, $config->getConfig()->toArray(), $entityClass);
            if ($this->hasAttribute($route, RestRouteOptionsResolver::ID_PLACEHOLDER)) {
                $this->addIdRequirement(
                    $annotation,
                    $entityClass,
                    $route->getRequirement(RestRouteOptionsResolver::ID_ATTRIBUTE)
                );
            }
            if ($config->hasConfigExtra(FiltersConfigExtra::NAME) && method_exists($config, 'getFilters')) {
                $this->addFilters($annotation, $config->getFilters());
            }
        }
        $formatRequirement = $route->getRequirement(RestRouteOptionsResolver::FORMAT_ATTRIBUTE);
        if ($formatRequirement) {
            $this->addFormatRequirement($annotation, $formatRequirement);
        }
    }

    /**
     * @param Route $route
     *
     * @return string|null
     */
    protected function getEntityClass(Route $route)
    {
        $pluralAlias = $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE);

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
        $context->removeConfigExtra(SortersConfigExtra::NAME);
        $context->addConfigExtra(new DescriptionsConfigExtra());
        $context->getRequestType()->add(RequestType::REST);
        if ('rest_json_api' === $this->docViewDetector->getView()) {
            $context->getRequestType()->add(RequestType::JSON_API);
        }
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
    protected function addFormatRequirement(ApiDoc $annotation, $requirement)
    {
        $annotation->addRequirement(
            RestRouteOptionsResolver::FORMAT_ATTRIBUTE,
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
            RestRouteOptionsResolver::ID_ATTRIBUTE,
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
                        $this->requestType,
                        $filter->isArrayAllowed()
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
