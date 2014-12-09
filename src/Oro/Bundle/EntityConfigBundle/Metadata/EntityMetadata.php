<?php

namespace Oro\Bundle\EntityConfigBundle\Metadata;

use Metadata\MergeableClassMetadata;
use Metadata\MergeableInterface;

class EntityMetadata extends MergeableClassMetadata
{
    /**
     * @var bool
     */
    public $configurable = false;

    /**
     * @var string
     */
    public $routeName;

    /**
     * @var string
     */
    public $routeView;

    /**
     * @var string
     */
    public $routeCreate;

    /**
     * @var string
     */
    public $mode;

    /**
     * @var array
     */
    public $defaultValues;

    /**
     * {@inheritdoc}
     */
    public function merge(MergeableInterface $object)
    {
        parent::merge($object);

        if ($object instanceof EntityMetadata) {
            $this->configurable  = $object->configurable;
            $this->defaultValues = $object->defaultValues;
            $this->routeName     = $object->routeName;
            $this->routeView     = $object->routeView;
            $this->routeCreate   = $object->routeCreate;
            $this->mode          = $object->mode;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->configurable,
                $this->defaultValues,
                $this->routeName,
                $this->routeView,
                $this->routeCreate,
                $this->mode,
                parent::serialize(),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($str)
    {
        list(
            $this->configurable,
            $this->defaultValues,
            $this->routeName,
            $this->routeView,
            $this->routeCreate,
            $this->mode,
            $parentStr
            ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
