<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class IndexMetadataBuilder implements MetadataBuilderInterface
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    /**
     * @param ConfigProvider                  $extendConfigProvider
     * @param ExtendDbIdentifierNameGenerator $nameGenerator
     */
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
        // TODO: need to be changed to fieldName => columnName
        // TODO: should be done in scope https://magecore.atlassian.net/browse/BAP-3940
        foreach ($indices as $columnName => $enabled) {
            $fieldConfig = $this->extendConfigProvider->getConfig($className, $columnName);

            if ($enabled && !$fieldConfig->is('state', ExtendScope::STATE_NEW)) {
                $indexName = $this->nameGenerator->generateIndexNameForExtendFieldVisibleInGrid(
                    $className,
                    $columnName
                );
                $metadataBuilder->addIndex([$columnName], $indexName);
            }
        }
    }
}
