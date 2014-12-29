<?php

namespace Oro\Component\Config\Resolver;

interface ResolverInterface
{
    /**
     * @param array $config
     * @param array $context
     *
     * @return array
     */
    public function resolve(array $config, array $context = array());
}
