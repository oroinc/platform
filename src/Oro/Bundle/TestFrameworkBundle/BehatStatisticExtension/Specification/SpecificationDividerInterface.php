<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification;

/**
 * Defines the contract for dividing test specifications into smaller chunks.
 *
 * Implementations of this interface split feature files into multiple suites based on
 * various strategies (count, execution time, etc.) for parallel test execution.
 */
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
