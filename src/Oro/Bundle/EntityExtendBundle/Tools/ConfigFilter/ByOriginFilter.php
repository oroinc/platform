<?php

namespace Oro\Bundle\EntityExtendBundle\Tools\ConfigFilter;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class ByOriginFilter extends AbstractFilter
{
    /** @var string[] */
    protected $skippedOrigins;

    /**
     * @param string[] $skippedOrigins
     */
    public function __construct(array $skippedOrigins)
    {
        $this->skippedOrigins = $skippedOrigins;
    }

    /**
     * {@inheritdoc}
     */
    protected function apply(ConfigInterface $config)
    {
        return
            $config->get('state') === ExtendScope::STATE_ACTIVE
            || !in_array($config->get('origin'), $this->skippedOrigins, true);
    }
}
