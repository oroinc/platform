<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

/**
 * This interface can be implemented by classes that depends on a RestDocViewDetector.
 */
interface RestDocViewDetectorAwareInterface
{
    /**
     * Sets the RestDocViewDetector.
     *
     * @param RestDocViewDetector $docViewDetector
     */
    public function setRestDocViewDetector(RestDocViewDetector $docViewDetector);
}
