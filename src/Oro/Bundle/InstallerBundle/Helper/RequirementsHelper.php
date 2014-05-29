<?php

namespace Oro\Bundle\InstallerBundle\Helper;

class RequirementsHelper
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        if (!class_exists('OroRequirements')) {
            require_once $rootDir . DIRECTORY_SEPARATOR . 'OroRequirements.php';
        }
    }

    /**
     * @return array
     */
    public function getNotFulfilledRequirements()
    {
        $collection = new \OroRequirements();

        return array_filter(
            $collection->getRequirements(),
            function ($requirement) {
                return !$requirement->isFulfilled();
            }
        );
    }
}
