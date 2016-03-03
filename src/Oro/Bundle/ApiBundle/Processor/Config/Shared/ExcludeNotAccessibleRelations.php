<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\Shared;

use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Excludes relations that are pointed to not accessible resources.
 * For example if entity1 has a reference to to entity2, but entity2 does not have Data API resource,
 * the relation will be excluded.
 */
class ExcludeNotAccessibleRelations implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RouterInterface */
    protected $router;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper  $doctrineHelper
     * @param RouterInterface $router
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        RouterInterface $router,
        ValueNormalizer $valueNormalizer
    ) {
        $this->doctrineHelper  = $doctrineHelper;
        $this->router          = $router;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $definition = $context->getResult();
        if (!$definition->isExcludeAll() || !$definition->hasFields()) {
            // expected completed configs
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->updateRelations($definition, $entityClass, $context->getRequestType());
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param RequestType            $requestType
     */
    protected function updateRelations(EntityDefinitionConfig $definition, $entityClass, RequestType $requestType)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        $fields   = $definition->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }

            $propertyPath = $field->getPropertyPath() ?: $fieldName;
            if (!$metadata->hasAssociation($propertyPath)) {
                continue;
            }

            $mapping        = $metadata->getAssociationMapping($propertyPath);
            $targetMetadata = $this->doctrineHelper->getEntityMetadataForClass($mapping['targetEntity']);
            if (!$this->isResourceForRelatedEntityAccessible($targetMetadata, $requestType)) {
                $field->setExcluded();
            }
        }
    }

    /**
     * @param ClassMetadata $targetMetadata
     * @param RequestType   $requestType
     *
     * @return bool
     */
    protected function isResourceForRelatedEntityAccessible(ClassMetadata $targetMetadata, RequestType $requestType)
    {
        if ($this->isResourceAccessible($targetMetadata->name, $requestType)) {
            return true;
        }
        if ($targetMetadata->inheritanceType !== ClassMetadata::INHERITANCE_TYPE_NONE) {
            // check that at least one inherited entity has Data API resource
            foreach ($targetMetadata->subClasses as $inheritedEntityClass) {
                if ($this->isResourceAccessible($inheritedEntityClass, $requestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return bool
     */
    protected function isResourceAccessible($entityClass, RequestType $requestType)
    {
        $result = false;

        $uri = $this->getEntityResourceUri($entityClass, $requestType);
        if ($uri) {
            $matchingContext = $this->router->getContext();

            $prevMethod = $matchingContext->getMethod();
            $matchingContext->setMethod('GET');
            try {
                $match = $this->router->match($uri);
                $matchingContext->setMethod($prevMethod);
                if ($this->isAcceptableMatch($match)) {
                    $result = true;
                }
            } catch (RoutingException $e) {
                // any exception from UrlMatcher means that the requested resource is not accessible
                $matchingContext->setMethod($prevMethod);
            }
        }

        return $result;
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function getEntityResourceUri($entityClass, RequestType $requestType)
    {
        $uri        = null;
        $entityType = $this->convertToEntityType($entityClass, $requestType);
        if ($entityType) {
            try {
                $uri = $this->router->generate(
                    'oro_rest_api_cget',
                    ['entity' => $entityType]
                );
            } catch (RoutingException $e) {
                // ignore any exceptions
            }
        }

        if ($uri) {
            $baseUrl = $this->router->getContext()->getBaseUrl();
            if ($baseUrl) {
                $uri = substr($uri, strlen($baseUrl));
            }
        }

        return $uri;
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function convertToEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }

    /**
     * @param array $match
     *
     * @return bool
     */
    protected function isAcceptableMatch(array $match)
    {
        // @todo: need to investigate how to avoid "'_webservice_definition' !== $match['_route']" check (BAP-8996)
        return
            isset($match['_route'])
            && '_webservice_definition' !== $match['_route'];
    }
}
