<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;

class ExtendMetadataBuilder
{
    /** @var ConfigProvider */
    protected $extendConfigProvider;

    /**
     * @var MetadataBuilderInterface[]
     */
    protected $builders = [];

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * Registers the metadata builder in the chain
     *
     * @param MetadataBuilderInterface $builder
     */
    public function addBuilder(MetadataBuilderInterface $builder)
    {
        $this->builders[] = $builder;
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
