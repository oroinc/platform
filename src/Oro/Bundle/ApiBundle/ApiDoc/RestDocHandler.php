<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Component\PhpUtils\ReflectionUtil;
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

class RestDocHandler implements HandlerInterface
{
    const ID_PLACEHOLDER = '{id}';

    /** @var RestDocViewDetector */
    protected $docViewDetector;

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var RestDocIdentifierHandler */
    protected $identifierHandler;

    /** @var RestDocFiltersHandler */
    protected $filtersHandler;

    /**
     * @param RestDocViewDetector         $docViewDetector
     * @param ActionProcessorBagInterface $processorBag
     * @param ValueNormalizer             $valueNormalizer
     * @param RestDocIdentifierHandler    $identifierHandler
     * @param RestDocFiltersHandler       $filtersHandler
     */
    public function __construct(
        RestDocViewDetector $docViewDetector,
        ActionProcessorBagInterface $processorBag,
        ValueNormalizer $valueNormalizer,
        RestDocIdentifierHandler $identifierHandler,
        RestDocFiltersHandler $filtersHandler
    ) {
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
     * @param Route $route
     *
     * @return string|null
     */
    protected function extractEntityTypeFromRoute(Route $route)
    {
        return $route->getDefault(RestRouteOptionsResolver::ENTITY_ATTRIBUTE);
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
     * @param string $entityType
     *
     * @return string
     */
    protected function getEntityClass($entityType)
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
    }

    /**
     * @param ApiDoc                 $annotation
     * @param EntityDefinitionConfig $config
     */
    protected function setDocumentation(ApiDoc $annotation, EntityDefinitionConfig $config)
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
    protected function setInputMetadata(
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
    protected function setOutputMetadata(
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
    protected function getDirectionValue($action, EntityDefinitionConfig $config, EntityMetadata $metadata)
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
    protected function setDirectionValue(ApiDoc $annotation, $direction, array $value)
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
    public function setStatusCodes(ApiDoc $annotation, EntityDefinitionConfig $config)
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
    protected function isActionWithInput($action)
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
    protected function isActionWithOutput($action)
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
    protected function getOutputAction($action)
    {
        if (in_array($action, [ApiActions::CREATE, ApiActions::UPDATE], true)) {
            return ApiActions::GET;
        }

        return $action;
    }
}
