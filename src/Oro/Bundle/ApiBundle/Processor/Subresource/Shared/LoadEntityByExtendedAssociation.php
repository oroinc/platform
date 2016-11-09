<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\AssociationNameGenerator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetRelationship\GetRelationshipContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource\GetSubresourceContext;

/**
 * Loads extended association entity using the EntitySerializer component.
 * As returned data is already normalized, the "normalize_data" group will be skipped.
  */
class LoadEntityByExtendedAssociation implements ProcessorInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /**
     * @param EntitySerializer $entitySerializer
     * @param ConfigProvider $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(
        EntitySerializer $entitySerializer,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetRelationshipContext|GetSubresourceContext $context */

        if (!$this->isApplicable($context)) {
            return;
        }

        $parentConfig = $context->getParentConfig();
        if (null === $parentConfig) {
            // a parent entity configuration does not exist
            return;
        }

        $associationConfig = $parentConfig->getField($context->getAssociationName());
        $associationDataType = $associationConfig->getDataType();
        if (!DataType::isExtendedAssociation($associationDataType)) {
            // an association is not extended
            return;
        }

        list($type, $kind) = DataType::parseExtendedAssociation($associationDataType);
        if ($type !== RelationType::MANY_TO_ONE) {
            // only many-to-one association is supported
            return;
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $context->getQuery();

        $result = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$result) {
            // associated entity not found
            return;
        }

        $target = $result->{AssociationNameGenerator::generateGetTargetMethodName($kind)}();
        $targetClassName = ClassUtils::getRealClass(get_class($target));

        $config = $context->getConfig();
        if ($context instanceof GetSubresourceContext) {
            $configExtras = [
                new EntityDefinitionConfigExtra(ApiActions::GET),
                new CustomizeLoadedDataConfigExtra(),
                new DataTransformersConfigExtra()
            ];

            $version = $context->getVersion();
            $requestType = $context->getRequestType();

            $config = $this->configProvider->getConfig($targetClassName, $version, $requestType, $configExtras)
                ->getDefinition();
            $metadata = $this->metadataProvider->getMetadata($targetClassName, $version, $requestType, $config);
            $context->setMetadata($metadata);
        }

        $result = $this->entitySerializer->serializeEntities([$target], $targetClassName, $config);

        if (empty($result)) {
            $result = null;
        } elseif (count($result) === 1) {
            $result = reset($result);
        } else {
            throw new RuntimeException('The result must have one or zero items.');
        }

        $context->setResult($result);

        // data returned by the EntitySerializer are already normalized
        $context->skipGroup('normalize_data');
    }

    /**
     * Checks if processor is applicable for current context
     * @param ContextInterface $context
     *
     * @return bool
     */
    protected function isApplicable(ContextInterface $context)
    {
        /** @var GetRelationshipContext|GetSubresourceContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return false;
        }

        $query = $context->getQuery();
        if (!$query instanceof QueryBuilder) {
            // unsupported query
            return false;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // an entity configuration does not exist
            return false;
        }

        return true;
    }
}
