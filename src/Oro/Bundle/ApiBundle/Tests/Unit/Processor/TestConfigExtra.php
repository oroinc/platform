<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class TestConfigExtra implements ConfigExtraInterface
{
    /** @var string */
    protected $name;

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
        $context->set($this->name, true);
    }
}
