<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

interface MetadataBuilderInterface
{
    /**
     * @param ConfigInterface $extendConfig The 'extend' config of the entity
     *                                      for which Doctrine metadata should be built
     * @return bool
     */
    public function supports(ConfigInterface $extendConfig);

    /**
     * @param ClassMetadataBuilder $metadataBuilder
     * @param ConfigInterface      $extendConfig    The 'extend' config of the entity
     *                                              for which Doctrine metadata should be built
     */
    public function build(ClassMetadataBuilder $metadataBuilder, ConfigInterface $extendConfig);
}
