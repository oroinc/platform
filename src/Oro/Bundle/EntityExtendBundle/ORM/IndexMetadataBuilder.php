<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityBundle\EntityConfig\IndexScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

/**
 * Builder for extend config indexes
 */
class IndexMetadataBuilder implements MetadataBuilderInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    public function __construct(
        ConfigProvider $extendConfigProvider,
        ExtendDbIdentifierNameGenerator $nameGenerator
    ) {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->nameGenerator        = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ConfigInterface $extendConfig)
    {
        return $extendConfig->is('index');
    }

    /**
     * {@inheritdoc}
     */
    public function build(ClassMetadataBuilder $metadataBuilder, ConfigInterface $extendConfig)
    {
        $className = $extendConfig->getId()->getClassName();
        $indices   = $extendConfig->get('index');
        // Should be changed to fieldName => columnName
        // in scope https://magecore.atlassian.net/browse/BAP-3940
        foreach ($indices as $columnName => $indexType) {
            $fieldConfig = $this->extendConfigProvider->getConfig($className, $columnName);
            if (\is_a(Type::getType($fieldConfig->getId()->getFieldType()), JsonType::class, true)) {
                continue;
            }

            $fieldState = $fieldConfig->get('state');
            if ($indexType && !in_array($fieldState, [ExtendScope::STATE_NEW, ExtendScope::STATE_DELETE])) {
                $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $columnName
                );
                if ((int)$indexType === IndexScope::INDEX_UNIQUE) {
                    $metadataBuilder->addUniqueConstraint([$columnName], $indexName);
                } else {
                    $metadataBuilder->addIndex([$columnName], $indexName);
                }
            }
        }
    }
}
