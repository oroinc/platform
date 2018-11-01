<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtraInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class TestConfigExtra implements ConfigExtraInterface
{
    /** @var string */
    private $name;

    /** @var array */
    private $contextAttributes;

    /**
     * @param string $name
     * @param array  $contextAttributes
     */
    public function __construct($name, array $contextAttributes = [])
    {
        $this->name = $name;
        $this->contextAttributes = $contextAttributes;
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
        foreach ($this->contextAttributes as $name => $value) {
            $context->set($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isPropagable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKeyPart()
    {
        return null;
    }
}
