<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

class BeforeEntityAttributeSaveEvent extends Event
{
    /** @var string */
    protected $alias;

    /** @var EntityConfigModel */
    protected $entityConfigModel;

    /** @var string */
    protected $options;

    /**
     * @param string $alias
     * @param EntityConfigModel $entityConfigModel
     * @param array $options
     */
    public function __construct($alias, $entityConfigModel, $options)
    {
        $this->alias                = $alias;
        $this->entityConfigModel    = $entityConfigModel;
        $this->options              = $options;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return EntityConfigModel
     */
    public function getEntityConfigModel()
    {
        return $this->entityConfigModel;
    }
}
