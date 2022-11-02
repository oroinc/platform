<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\Extra\ConfigExtraSectionInterface;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

class TestConfigSection implements ConfigExtraSectionInterface
{
    /** @var string */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ConfigContext $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigType()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return null;
    }
}
