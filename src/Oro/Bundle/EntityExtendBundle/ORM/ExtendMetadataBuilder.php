<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

/**
 * The builder for extendable entities ORM metadata.
 */
class ExtendMetadataBuilder
{
    /** @var ConfigProvider */
    private $extendConfigProvider;

    /** @var iterable|MetadataBuilderInterface[] */
    private $builders;

    /**
     * @param ConfigProvider                      $extendConfigProvider
     * @param iterable|MetadataBuilderInterface[] $builders
     */
    public function __construct(ConfigProvider $extendConfigProvider, iterable $builders)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->builders = $builders;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function supports($className)
    {
        return
            !ConfigHelper::isConfigModelEntity($className) &&
            $this->extendConfigProvider->hasConfig($className) &&
            $this->extendConfigProvider->getConfig($className)->is('is_extend');
    }

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param string               $className
     */
    public function build(ClassMetadataBuilder $metadataBuilder, $className)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($className);
        foreach ($this->builders as $builder) {
            if ($builder->supports($extendConfig)) {
                $builder->build($metadataBuilder, $extendConfig);
            }
        }
    }
}
