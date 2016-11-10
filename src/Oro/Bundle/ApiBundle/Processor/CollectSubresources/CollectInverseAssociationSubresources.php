<?php

namespace Oro\Bundle\ApiBundle\Processor\CollectSubresources;

use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\ApiSubresource;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider as EntityConfigProvider;

class CollectInverseAssociationSubresources extends LoadSubresources
{
    /** @var ConfigLoaderFactory */
    protected $configLoaderFactory;

    /** @var ConfigBag */
    protected $configBag;

    /** @var EntityConfigProvider */
    protected $extendConfigProvider;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ConfigLoaderFactory $configLoaderFactory
     * @param ConfigBag           $configBag
     * @param ConfigProvider      $configProvider
     * @param MetadataProvider    $metadataProvider
     */
    public function __construct(
        ConfigLoaderFactory $configLoaderFactory,
        ConfigBag $configBag,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider,
        EntityConfigProvider $extendConfigProvider,
        ValueNormalizer $valueNormalizer
    ) {
        parent::__construct($configProvider, $metadataProvider);
        $this->configLoaderFactory = $configLoaderFactory;
        $this->configBag = $configBag;
        $this->extendConfigProvider = $extendConfigProvider;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CollectSubresourcesContext $context */

        $version = $context->getVersion();
        $subresources = $context->getResult();

        $resources = $context->getResources();

        foreach (array_keys($resources) as $entityClass) {
            $config = $this->configBag->getConfig($entityClass, $version);
            if (is_array($config) && array_key_exists('fields', $config)) {
                foreach ($config['fields'] as $fieldConfig) {
                    if (array_key_exists('data_type', $fieldConfig)
                        && DataType::isExtendedAssociation($fieldConfig['data_type'])
                    ) {
                        list($type, $kind) = DataType::parseExtendedAssociation($fieldConfig['data_type']);
                        if ($type === RelationType::MANY_TO_ONE) {
                            $subresource = new ApiSubresource();
                            $subresource->setTargetClassName($entityClass);
                            $subresource->setAcceptableTargetClassNames([$entityClass]);
                            $subresource->setIsCollection(true);

                            $subresource->setExcludedActions(['delete_relationship', 'update_relationship']);

                            $associationName = ValueNormalizerUtil::convertToEntityType(
                                $this->valueNormalizer,
                                $entityClass,
                                $context->getRequestType()
                            );

                            $extendConfig = $this->extendConfigProvider->getConfig($entityClass);
                            $relations = $extendConfig->get('relation', []);

                            foreach ($relations as $relationData) {
                                if ($this->isSupportedTarget($relationData, $kind)) {
                                    $targetClass = $relationData['target_entity'];

                                    $targetSubresources = $subresources->get($targetClass);
                                    $targetSubresources->addSubresource($associationName, $subresource);
                                }
                            }
                        }
                    }
                }
            }
        }

        $context->setResult($subresources);
    }

    /**
     * @param array $relationData
     *
     * @return bool
     */
    protected function isSupportedTarget(array $relationData, $targetClass)
    {
        /** @var FieldConfigId $fieldConfigId */
        $fieldConfigId = $relationData['field_id'];

        return
            $fieldConfigId instanceof FieldConfigId
            && $fieldConfigId->getFieldType() === RelationType::MANY_TO_ONE
            && $fieldConfigId->getFieldName() === ExtendHelper::buildAssociationName(
                $relationData['target_entity'],
                $targetClass
            );
    }
}