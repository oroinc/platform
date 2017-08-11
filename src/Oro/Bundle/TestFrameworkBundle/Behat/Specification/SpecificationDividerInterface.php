<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Specification;

interface SpecificationDividerInterface
{
    /**
     * @param string $baseName e.g. AcmeSuite
     * @param array $array
     * @param int $divider
     * @return array
     */
    public function divide($baseName, array $array, $divider);
}
