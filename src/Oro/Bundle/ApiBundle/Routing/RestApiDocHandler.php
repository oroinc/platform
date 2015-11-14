<?php

namespace Oro\Bundle\ApiBundle\Routing;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ActionProcessorBag;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
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
            'description'            => 'Get {name}',
            'documentation'          => 'Get {name}',
            'fallback_description'   => 'Get {class}',
            'fallback_documentation' => 'Get {class}',
            'get_name_method'        => 'getEntityClassName'
        ],
        'get_list' => [
            'description'            => 'Get {name}',
            'documentation'          => 'Get {name}',
            'fallback_description'   => 'Get values of {class}',
            'fallback_documentation' => 'Get values of {class}',
            'get_name_method'        => 'getEntityClassPluralName'
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

        $pluralAlias = $route->getDefault(RestApiRouteOptionsResolver::ENTITY_ATTRIBUTE);
        $entityClass = null;
        if ($pluralAlias) {
            $entityClass = $this->entityAliasResolver->getClassByPluralAlias($pluralAlias);
            $this->setDescription($annotation, $action, $entityClass);
            $versionRequirement = $route->getRequirement(RestApiRouteOptionsResolver::VERSION_ATTRIBUTE);
            if ($versionRequirement) {
                $this->addVersionRequirement($annotation, $versionRequirement);
            }
            $formatRequirement = $route->getRequirement(RestApiRouteOptionsResolver::FORMAT_ATTRIBUTE);
            if ($formatRequirement) {
                $this->addFormatRequirement($annotation, $formatRequirement);
            }
            if ($this->hasAttribute($route, RestApiRouteOptionsResolver::ID_PLACEHOLDER)) {
                $this->addIdRequirement(
                    $annotation,
                    $entityClass,
                    $route->getRequirement(RestApiRouteOptionsResolver::ID_ATTRIBUTE)
                );
            }
        }

        $this->addFilters($annotation, $action, $entityClass);
    }

    /**
     * @param ApiDoc $annotation
     * @param string $action
     * @param string $entityClass
     */
    protected function setDescription(ApiDoc $annotation, $action, $entityClass)
    {
        $templates     = $this->templates[$action];
        $getNameMethod = $templates['get_name_method'];
        $entityName    = $this->entityClassNameProvider->{$getNameMethod}($entityClass);
        if ($entityName) {
            $annotation->setDescription(
                strtr($templates['description'], ['{name}' => $entityName])
            );
            $annotation->setDocumentation(
                strtr($templates['documentation'], ['{name}' => $entityName])
            );
        } else {
            $annotation->setDescription(
                strtr($templates['fallback_description'], ['{class}' => $entityClass])
            );
            $annotation->setDocumentation(
                strtr($templates['fallback_documentation'], ['{class}' => $entityClass])
            );
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
        $metadata = $this->doctrineHelper->getEntityMetadata($entityClass);
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
     * @param ApiDoc      $annotation
     * @param string      $action
     * @param string|null $entityClass
     */
    protected function addFilters(ApiDoc $annotation, $action, $entityClass)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->setRequestType(RequestType::REST);
        $context->setLastGroup('initialize');
        if ($entityClass) {
            $context->setClassName($entityClass);
        }
        $processor->process($context);

        $filters = $context->getFilters();
        foreach ($filters as $key => $filter) {
            if ($filter instanceof StandaloneFilter) {
                $options = [
                    'description' => $filter->getDescription(),
                    'requirement' => $this->valueNormalizer->getRequirement(
                        $filter->getDataType(),
                        RequestType::REST
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
