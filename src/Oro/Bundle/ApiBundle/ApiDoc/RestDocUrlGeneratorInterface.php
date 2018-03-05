<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * The interface for classes that can build URL for API view.
 */
interface RestDocUrlGeneratorInterface
{
    /**
     * Returns URL for a specific API view.
     *
     * @param string $view
     *
     * @return string
     *
     * @throws \InvalidArgumentException if the given API view is undefined
     */
    public function generate(string $view): string;
}
