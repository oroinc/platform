<?php

namespace Oro\Bundle\EntityExtendBundle\ORM;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

/**
 * Defines the contract for building Doctrine ORM metadata for extended entities.
 *
 * Implementations of this interface are responsible for examining extend configurations
 * and building appropriate Doctrine metadata (mappings, relations, etc.) for custom entities
 * and fields. Each builder can support specific types of extend configurations and should
 * indicate whether it can handle a given configuration through the {@see MetadataBuilderInterface::supports()} method.
 */
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
