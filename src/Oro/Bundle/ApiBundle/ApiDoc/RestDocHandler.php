<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\Parser\ApiDocMetadata;
use Oro\Bundle\ApiBundle\Config\DescriptionsConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\Routing\Route;

/**
 * Populates ApiDoc annotation based on the confoguration of Data API resource.
 */
class RestDocHandler implements HandlerInterface
{
    private const ID_PLACEHOLDER = '{id}';

    /** @var string The group of routes that should be processed by this handler */
    private $routeGroup;

    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var ActionProcessorBagInterface */
    private $processorBag;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /** @var RestDocIdentifierHandler */
    private $identifierHandler;

    /** @var RestDocFiltersHandler */
    private $filtersHandler;

    /**
     * @param string                      $routeGroup
     * @param RestDocViewDetector         $docViewDetector
     * @param ActionProcessorBagInterface $processorBag
     * @param ValueNormalizer             $valueNormalizer
     * @param RestDocIdentifierHandler    $identifierHandler
     * @param RestDocFiltersHandler       $filtersHandler
     */
    public function __construct(
        string $routeGroup,
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        ValueNormalizer $valueNormalizer,
        RestDocIdentifierHandler $identifierHandler,
        RestDocFiltersHandler $filtersHandler
    ) {
        $this->routeGroup = $routeGroup;
        $this->docViewDetector = $docViewDetector;
        $this->processorBag = $processorBag;
        $this->valueNormalizer = $valueNormalizer;
        $this->identifierHandler = $identifierHandler;
        $this->filtersHandler = $filtersHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption(RestRouteOptionsResolver::GROUP_OPTION) !== $this->routeGroup
            || $this->docViewDetector->getRequestType()->isEmpty()
        ) {
            return;
        }
        $action = $route->getDefault(RestRouteOptionsResolver::ACTION_ATTRIBUTE);
        if (!$action) {
            return;
        }
        $entityType = $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE);
        if (!$entityType) {
            return;
        }

        $entityClass = $this->getEntityClass($entityType);
        $associationName = $route->getDefault(RestRouteOptionsResolver::ASSOCIATION_ATTRIBUTE);
        $context = $this->getContext($action, $entityClass, $associationName);
        $config = $context->getConfig();
        $metadata = $context->getMetadata();

        $annotation->setSection($entityType);
        $this->setDescription($annotation, $config);
        $this->setDocumentation($annotation, $config);
        if ($this->hasAttribute($route, self::ID_PLACEHOLDER)) {
            $this->identifierHandler->handle(
                $annotation,
                $route,
                $associationName ? $context->getParentMetadata() : $metadata
            );
        }
        $this->setInputMetadata($annotation, $action, $config, $metadata);
        $this->setOutputMetadata($annotation, $entityClass, $action, $config, $metadata, $associationName);
        $this->filtersHandler->handle($annotation, $context->getFilters(), $metadata);
        $this->setStatusCodes($annotation, $config);
    }

    /**
     * Checks if a route has the given placeholder in a path.
     *
     * @param Route  $route
     * @param string $placeholder
     *
     * @return bool
     */
    private function hasAttribute(Route $route, $placeholder)
    {
        return false !== strpos($route->getPath(), $placeholder);
    }

    /**
     * @param string $entityType
     *
     * @return string
     */
    private function getEntityClass($entityType)
    {
        return ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->docViewDetector->getRequestType()
        );
    }

    /**
     * @param string      $action
     * @param string      $entityClass
     * @param string|null $associationName
     *
     * @return Context|SubresourceContext
     */
    private function getContext($action, $entityClass, $associationName = null)
    {
        $processor = $this->processorBag->getProcessor($action);
        /** @var Context $context */
        $context = $processor->createContext();
        $context->addConfigExtra(new DescriptionsConfigExtra());
        $context->getRequestType()->set($this->docViewDetector->getRequestType());
        $context->setLastGroup('initialize');
        if ($associationName) {
            /** @var SubresourceContext $context */
            $context->setParentClassName($entityClass);
            $context->setAssociationName($associationName);
            $parentConfigExtras = $context->getParentConfigExtras();
            $parentConfigExtras[] = new DescriptionsConfigExtra();
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
    private function setDescription(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $description = $config->getDescription();
        if ($description) {
            $annotation->setDescription($description);
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setDocumentation(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $documentation = $config->getDocumentation();
        if ($documentation) {
            $annotation->setDocumentation($documentation);
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param string                 $action
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     */
    private function setInputMetadata(
        ApiDoc $annotation,
        $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata
    ) {
        if ($this->isActionWithInput($action)) {
            $this->setDirectionValue($annotation, 'input', $this->getDirectionValue($action, $config, $metadata));
        }
    }

    /**
     * @param ApiDoc                 $annotation
     * @param string                 $entityClass
     * @param string                 $action
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     * @param string|null            $associationName
     */
    private function setOutputMetadata(
        ApiDoc $annotation,
        $entityClass,
        $action,
        EntityDefinitionConfig $config,
        EntityMetadata $metadata,
        $associationName = null
    ) {
        if ($this->isActionWithOutput($action)) {
            // check if output format should be taken from another action type. In this case
            // entity metadata and config will be taken for the action, those format should be used
            $substituteAction = $this->getOutputAction($action);
            if ($action !== $substituteAction) {
                $substituteContext = $this->getContext($substituteAction, $entityClass, $associationName);
                $config = $substituteContext->getConfig();
                $metadata = $substituteContext->getMetadata();
            }

            $this->setDirectionValue($annotation, 'output', $this->getDirectionValue($action, $config, $metadata));
        }
    }

    /**
     * @param string                 $action
     * @param EntityDefinitionConfig $config
     * @param EntityMetadata         $metadata
     *
     * @return array
     */
    private function getDirectionValue($action, EntityDefinitionConfig $config, EntityMetadata $metadata)
    {
        return [
            'class'   => null,
            'options' => [
                'metadata' => new ApiDocMetadata(
                    $action,
                    $metadata,
                    $config,
                    $this->docViewDetector->getRequestType()
                )
            ]
        ];
    }

    /**
     * @param ApiDoc $annotation
     * @param string $direction
     * @param array  $value
     */
    private function setDirectionValue(ApiDoc $annotation, $direction, array $value)
    {
        // unfortunately there is no other way to update "input" and "output" parameters
        // except to use the reflection
        $outputProperty = ReflectionUtil::getProperty(new \ReflectionClass($annotation), $direction);
        $outputProperty->setAccessible(true);
        $outputProperty->setValue($annotation, $value);
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    private function setStatusCodes(ApiDoc $annotation, EntityDefinitionConfig $config)
    {
        $statusCodes = $config->getStatusCodes();
        if (null !== $statusCodes) {
            $codes = $statusCodes->getCodes();
            ksort($codes);
            foreach ($codes as $statusCode => $code) {
                if (!$code->isExcluded()) {
                    $annotation->addStatusCode($statusCode, $code->getDescription());
                }
            }
        }
    }

    /**
     * Returns true in case if the given action receives resource data.
     *
     * @param string $action
     *
     * @return bool
     */
    private function isActionWithInput($action)
    {
        return in_array(
            $action,
            [ApiActions::CREATE, ApiActions::UPDATE, ApiActions::UPDATE_RELATIONSHIP, ApiActions::ADD_RELATIONSHIP],
            true
        );
    }

    /**
     * Returns true in case if the given action returns resource data.
     *
     * @param string $action
     *
     * @return bool
     */
    private function isActionWithOutput($action)
    {
        return !in_array(
            $action,
            [ApiActions::DELETE, ApiActions::DELETE_LIST, ApiActions::DELETE_RELATIONSHIP],
            true
        );
    }

    /**
     * Returns action name that should be used to format output data.
     *
     * @param string $action
     *
     * @return string
     */
    private function getOutputAction($action)
    {
        if (in_array($action, [ApiActions::CREATE, ApiActions::UPDATE], true)) {
            return ApiActions::GET;
        }

        return $action;
    }
}
