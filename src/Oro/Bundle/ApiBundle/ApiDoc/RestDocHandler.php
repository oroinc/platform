<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Component\PhpUtils\ReflectionUtil;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class RestDocHandler implements HandlerInterface
{
    const ID_ATTRIBUTE   = 'id';
    const ID_PLACEHOLDER = '{id}';

    const ID_DESCRIPTION = 'The identifier of an entity';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param RestDocViewDetector         $docViewDetector
     * @param ActionProcessorBagInterface $processorBag
     * @param ValueNormalizer             $valueNormalizer
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        ValueNormalizer $valueNormalizer
    ) {
        $this->docViewDetector = $docViewDetector;
        $this->processorBag = $processorBag;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption('group') !== RestRouteOptionsResolver::ROUTE_GROUP
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }
        $action = $route->getDefault('_action');
        if (!$action) {
            return;
        }

        $entityType = $this->extractEntityTypeFromRoute($route);
        if ($entityType) {
            $annotation->setSection($entityType);

            $entityClass = $this->valueNormalizer->normalizeValue(
                $entityType,
                DataType::ENTITY_CLASS,
                $this->docViewDetector->getRequestType()
            );
            $associationName = $route->getDefault(RestRouteOptionsResolver::ASSOCIATION_ATTRIBUTE);
            $actionContext = $this->getContext($action, $entityClass, $associationName);
            $config = $actionContext->getConfig();

            $this->setDescription($annotation, $config);
            $statusCodes = $config->getStatusCodes();
            if ($statusCodes) {
                $this->setStatusCodes($annotation, $statusCodes);
            }
            if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
                if ($associationName) {
                    $this->addIdRequirement($annotation, $route, $actionContext->getParentMetadata());
                } else {
                    $this->addIdRequirement($annotation, $route, $actionContext->getMetadata());
                }
            }
            $filters = $actionContext->getFilters();
            if (!$filters->isEmpty()) {
                $this->addFilters($annotation, $filters, $actionContext->getMetadata());
            }
            $this->sortFilters($annotation);
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
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getEntityType($entityClass)
    {
        try {
            return $this->valueNormalizer->normalizeValue(
                $entityClass,
                DataType::ENTITY_TYPE,
                $this->docViewDetector->getRequestType()
            );
        } catch (\Exception $e) {
            // ignore any exception here
        }

        return null;
    }

    /**
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     *
     * @return Context|SubresourceContext
     */
    protected function getContext($action, $entityClass, $associationName = null)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->addConfigExtra(new DescriptionsConfigExtra($action));
        $context->getRequestType()->set($this->docViewDetector->getRequestType());
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
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    protected function setDescription(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $description = $config->getDescription();
        if ($description) {
            $annotation->setDescription($description);
        }
        $documentation = $config->getDocumentation();
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
     * @param ApiDoc         $annotation
     * @param Route          $route
     * @param EntityMetadata $metadata
     */
    protected function addIdRequirement(ApiDoc $annotation, Route $route, EntityMetadata $metadata)
    {
        $idFields = $metadata->getIdentifierFieldNames();
        $dataType = DataType::STRING;
        if (count($idFields) === 1) {
            $field = $metadata->getField(reset($idFields));
            if (!$field) {
                throw new \RuntimeException(
                    sprintf(
                        'The metadata for "%s" entity does not contains "%s" identity field. Resource: %s %s',
                        $metadata->getClassName(),
                        reset($idFields),
                        implode(' ', $route->getMethods()),
                        $route->getPath()
                    )
                );
            }
            $dataType = $field->getDataType();
        }

        $annotation->addRequirement(
            self::ID_ATTRIBUTE,
            [
                'dataType'    => ApiDocDataTypeConverter::convertToApiDocDataType($dataType),
                'requirement' => $this->getIdRequirement($metadata),
                'description' => self::ID_DESCRIPTION
            ]
        );
    }

    /**
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    protected function getIdRequirement(EntityMetadata $metadata)
    {
        $idFields = $metadata->getIdentifierFieldNames();
        $idFieldCount = count($idFields);
        if ($idFieldCount === 1) {
            // single identifier
            return $this->getIdFieldRequirement($metadata->getField(reset($idFields))->getDataType());
        }

        // combined identifier
        $requirements = [];
        foreach ($idFields as $field) {
            $requirements[] = $field . '=' . $this->getIdFieldRequirement($metadata->getField($field)->getDataType());
        }

        return implode(',', $requirements);
    }

    /**
     * @param string $fieldType
     *
     * @return string
     */
    protected function getIdFieldRequirement($fieldType)
    {
        $result = $this->valueNormalizer->getRequirement($fieldType, $this->docViewDetector->getRequestType());

        if (ValueNormalizer::DEFAULT_REQUIREMENT === $result) {
            $result = '[^\.]+';
        }

        return $result;
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
                        $this->docViewDetector->getRequestType(),
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
                        $targetEntityType = $this->getEntityType($targetClassName);
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
     * @param ApiDoc $annotation
     */
    protected function sortFilters(ApiDoc $annotation)
    {
        $filters = $annotation->getFilters();
        if (!empty($filters)) {
            ksort($filters);
            // unfortunately there is no other way to update filters except to use the reflection
            $filtersProperty = ReflectionUtil::getProperty(new \ReflectionClass($annotation), 'filters');
            $filtersProperty->setAccessible(true);
            $filtersProperty->setValue($annotation, $filters);
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
