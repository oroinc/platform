<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfigExtra;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class RestDocHandler implements HandlerInterface
{
    /** @var RequestTypeProviderInterface */
    protected $requestTypeProvider;

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var ResourceDocProviderInterface */
    protected $resourceDocProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RequestType */
    private $requestType;

    /**
     * @param RequestTypeProviderInterface $requestTypeProvider
     * @param ActionProcessorBagInterface  $processorBag
     * @param ResourceDocProviderInterface $resourceDocProvider
     * @param DoctrineHelper               $doctrineHelper
     * @param ValueNormalizer              $valueNormalizer
     */
    public function __construct(
        RequestTypeProviderInterface $requestTypeProvider,
        ActionProcessorBagInterface $processorBag,
        ResourceDocProviderInterface $resourceDocProvider,
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->requestTypeProvider = $requestTypeProvider;
        $this->processorBag = $processorBag;
        $this->doctrineHelper = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
        $this->resourceDocProvider = $resourceDocProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption('group') !== RestRouteOptionsResolver::ROUTE_GROUP) {
            return;
        }
        if ($this->getRequestType()->isEmpty()) {
            return;
        }
        $action = $route->getDefault('_action');
        if (empty($action)) {
            return;
        }

        $entityType = $this->extractEntityTypeFromRoute($route);
        if ($entityType) {
            $entityClass = $this->getEntityClass($entityType);
            $associationName = $route->getDefault(RestRouteOptionsResolver::ASSOCIATION_ATTRIBUTE);

            $actionContext = $this->getContext($action, $entityClass, $associationName);
            $config = $actionContext->getConfig();

            $annotation->setSection($entityType);
            $this->setDescription($annotation, $action, $actionContext);
            $statusCodes = $config->getStatusCodes();
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
            $filters = $actionContext->getFilters();
            if (!$filters->isEmpty()) {
                $this->addFilters($annotation, $filters, $actionContext->getMetadata());
            }
        }
    }

    /**
     * @param Route $route
     *
     * @return string|null
     */
    protected function extractEntityTypeFromRoute(Route $route)
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
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->getRequestType()
        );
    }

    /**
     * @param string $entityClass
     * @param bool   $throwException
     *
     * @return string|null
     */
    protected function getEntityType($entityClass, $throwException = true)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->getRequestType(),
            $throwException
        );
    }

    /**
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     *
     * @return Context
     */
    protected function getContext($action, $entityClass, $associationName = null)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->removeConfigExtra(SortersConfigExtra::NAME);
        $context->addConfigExtra(new DescriptionsConfigExtra($action));
        $context->addConfigExtra(new StatusCodesConfigExtra($action));
        $context->getRequestType()->set($this->getRequestType()->toArray());
        $context->setLastGroup('initialize');
        if ($associationName) {
            /** @var SubresourceContext $context */
            $context->setParentClassName($entityClass);
            $context->setAssociationName($associationName);
            $parentConfigExtras = $context->getParentConfigExtras();
            $parentConfigExtras[] = new DescriptionsConfigExtra($action);
            $context->setParentConfigExtras($parentConfigExtras);
        } else {
            $context->setClassName($entityClass);
        }

        $processor->process($context);

        return $context;
    }

    /**
     * @param ApiDoc  $annotation
     * @param string  $action
     * @param Context $actionContext
     */
    protected function setDescription(ApiDoc $annotation, $action, Context $actionContext)
    {
        $requestType = $this->getRequestType();
        if ($actionContext instanceof SubresourceContext) {
            $parentEntityClass = $actionContext->getParentClassName();
            $associationName = $actionContext->getAssociationName();
            $parentConfig = $actionContext->getParentConfig()->toArray();
            $description = $this->resourceDocProvider->getSubresourceDescription(
                $action,
                Version::LATEST,
                $requestType,
                $parentConfig,
                $parentEntityClass,
                $associationName
            );
            $documentation = $this->resourceDocProvider->getSubresourceDocumentation(
                $action,
                Version::LATEST,
                $requestType,
                $parentConfig,
                $parentEntityClass,
                $associationName
            );
        } else {
            $entityClass = $actionContext->getClassName();
            $config = $actionContext->getConfig()->toArray();
            $description = $this->resourceDocProvider->getResourceDescription(
                $action,
                Version::LATEST,
                $requestType,
                $config,
                $entityClass
            );
            $documentation = $this->resourceDocProvider->getResourceDocumentation(
                $action,
                Version::LATEST,
                $requestType,
                $config,
                $entityClass
            );
        }
        if ($description) {
            $annotation->setDescription($description);
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
                'description' => $this->resourceDocProvider->getIdentifierDescription($this->getRequestType())
            ]
        );
    }

    /**
     * @param ApiDoc           $annotation
     * @param FilterCollection $filters
     * @param EntityMetadata   $metadata
     */
    protected function addFilters(ApiDoc $annotation, FilterCollection $filters, EntityMetadata $metadata)
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

                if ($filter instanceof ComparisonFilter && $metadata->hasAssociation($filter->getField())) {
                    $targetClassNames = $metadata->getAssociation($filter->getField())
                        ->getAcceptableTargetClassNames();
                    $targetEntityTypes = [];
                    foreach ($targetClassNames as $targetClassName) {
                        $targetEntityType = $this->getEntityType($targetClassName, false);
                        if ($targetEntityType) {
                            $targetEntityTypes[] = $targetEntityType;
                        }
                    }
                    if (!empty($targetEntityTypes)) {
                        $options['relation'] = implode(',', $targetEntityTypes);
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
            $this->requestType = $this->requestTypeProvider->getRequestType() ?: new RequestType([]);
        }

        return $this->requestType;
    }
}
