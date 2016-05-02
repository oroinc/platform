<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;

class RestDocHandler implements HandlerInterface
{
    const JSON_API_VIEW = 'rest_json_api';

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
        'delete' => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => EntityDefinitionConfig::LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
        'delete_list' => [
            'description'          => 'Delete {name}',
            'fallback_description' => 'Delete a list of {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => EntityDefinitionConfig::PLURAL_LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
        'create' => [
            'description'          => 'Create {name}',
            'fallback_description' => 'Create {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => EntityDefinitionConfig::LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
        'update' => [
            'description'          => 'Update {name}',
            'fallback_description' => 'Update {class}',
            'get_name_method'      => 'getEntityClassName',
            'description_key'      => EntityDefinitionConfig::LABEL,
            'documentation_key'    => EntityDefinitionConfig::DESCRIPTION
        ],
    ];

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

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
     * @param DoctrineHelper                   $doctrineHelper
     * @param ValueNormalizer                  $valueNormalizer
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        EntityClassNameProviderInterface $entityClassNameProvider,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->docViewDetector         = $docViewDetector;
        $this->processorBag            = $processorBag;
        $this->entityClassNameProvider = $entityClassNameProvider;
        $this->doctrineHelper          = $doctrineHelper;
        $this->valueNormalizer         = $valueNormalizer;
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

        $entityType = $this->getEntityType($route);
        if ($entityType) {
            $entityClass = $this->getEntityClass($entityType);
            $config = $this->getConfig($action, $entityClass);
            $statusCodes = $config->getConfig()->getStatusCodes();
            $config->getConfig()->setStatusCodes();

            $annotation->setSection($entityType);
            $this->setDescription($annotation, $action, $config->getConfig()->toArray(), $entityClass);
            if ($statusCodes) {
                $this->setStatusCodes($annotation, $statusCodes);
            }
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
        if (self::JSON_API_VIEW === $this->docViewDetector->getView()) {
            $this->removeFormatRequirement($annotation);
            $this->removeFormatPlaceholder($route);
        } else {
            $formatRequirement = $route->getRequirement(RestRouteOptionsResolver::FORMAT_ATTRIBUTE);
            if ($formatRequirement) {
                $this->addFormatRequirement($annotation, $formatRequirement);
            }
        }
    }

    /**
     * @param Route $route
     *
     * @return string|null
     */
    protected function getEntityType(Route $route)
    {
        return $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE);
    }

    /**
     * @param string $entityType
     *
     * @return string
     */
    protected function getEntityClass($entityType)
    {
        return $this->valueNormalizer->normalizeValue(
            $entityType,
            DataType::ENTITY_CLASS,
            $this->getRequestType()
        );
    }

    /**
     * @param string $action
     * @param string $entityClass
     *
     * @return Context
     */
    protected function getConfig($action, $entityClass)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->removeConfigExtra(SortersConfigExtra::NAME);
        $context->addConfigExtra(new DescriptionsConfigExtra($action));
        $context->addConfigExtra(new StatusCodesConfigExtra($action));
        $this->buildRequestType($context->getRequestType());
        $context->setLastGroup('initialize');
        $context->setClassName($entityClass);

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
     * @param ApiDoc            $annotation
     * @param StatusCodesConfig $statusCodes
     */
    protected function setStatusCodes(ApiDoc $annotation, StatusCodesConfig $statusCodes)
    {
        $codes = $statusCodes->getCodes();
        foreach ($codes as $statusCode => $code) {
            if (!$code->isExcluded()) {
                $annotation->addStatusCode($statusCode, $code->getDescription());
            }
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
     */
    protected function removeFormatRequirement(ApiDoc $annotation)
    {
        $requirements = $annotation->getRequirements();
        if (array_key_exists(RestRouteOptionsResolver::FORMAT_ATTRIBUTE, $requirements)) {
            unset($requirements[RestRouteOptionsResolver::FORMAT_ATTRIBUTE]);
            // unfortunately ApiDoc::setRequirements method does a merge specified requirements
            // with existing ones and there is no other way to remove existing requirement
            // except to use the reflection
            $r = new \ReflectionClass($annotation);
            $p = $r->getProperty('requirements');
            $p->setAccessible(true);
            $p->setValue($annotation, $requirements);
        }
    }

    /**
     * @param Route $route
     */
    protected function removeFormatPlaceholder(Route $route)
    {
        $path = $route->getPath();
        $startPos = strpos($path, RestRouteOptionsResolver::FORMAT_PLACEHOLDER);
        if (false !== $startPos) {
            $route->setPath(substr($path, 0, $startPos));
        }
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
                        $this->getRequestType(),
                        $filter->isArrayAllowed()
                    )
                ];
                $operators = $filter->getSupportedOperators();
                if (!empty($operators) && !(count($operators) === 1 && $operators[0] === StandaloneFilter::EQ)) {
                    $options['operators'] = implode(',', $operators);
                }
                if ($filter instanceof StandaloneFilterWithDefaultValue) {
                    $default = $filter->getDefaultValueString();
                    if (!empty($default)) {
                        $options['default'] = $default;
                    }
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

    /**
     * @return RequestType
     */
    protected function getRequestType()
    {
        if (null === $this->requestType) {
            $this->requestType = new RequestType([]);
            $this->buildRequestType($this->requestType);
        }

        return $this->requestType;
    }

    /**
     * @param RequestType $requestType
     */
    protected function buildRequestType(RequestType $requestType)
    {
        $requestType->add(RequestType::REST);
        if (self::JSON_API_VIEW === $this->docViewDetector->getView()) {
            $requestType->add(RequestType::JSON_API);
        }
    }
}
