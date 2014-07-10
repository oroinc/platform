<?php

namespace Oro\Bundle\UIBundle\Provider;

/**
 * Provides an interface for services that provides labels
 */
interface LabelProviderInterface
{
    /**
     * Returns the label
     *
     * @param array $parameters
     *
     * @return string|null
     */
    public function getLabel(array $parameters);
}
