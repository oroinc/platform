<?php

namespace Oro\Bundle\FormBundle\Guesser;

/**
 * Interface used to implement for guesser that can tell frontend how to render specific field
 */
interface FormGuesserInterface
{
    /**
     * Returns FormBuildData for known types, null for unknown
     *
     * @param string $class FQCN
     * @param string|null $field
     * @return FormBuildData|null
     */
    public function guess($class, $field = null);
}
