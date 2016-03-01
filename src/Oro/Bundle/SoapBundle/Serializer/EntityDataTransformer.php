<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Oro\Component\EntitySerializer\ValueTransformer;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Component\EntitySerializer\EntityDataTransformer as BaseEntityDataTransformer;

/**
 * @deprecated since 1.9. use {@see Oro\Component\EntitySerializer\EntityDataTransformer}
 */
class EntityDataTransformer extends BaseEntityDataTransformer implements DataTransformerInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container, new ValueTransformer());
    }
}
