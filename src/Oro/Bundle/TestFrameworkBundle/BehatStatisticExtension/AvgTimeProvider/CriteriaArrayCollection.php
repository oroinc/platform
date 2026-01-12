<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\AvgTimeProvider;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection for storing criteria used in average time provider calculations.
 *
 * This class extends Doctrine's {@see ArrayCollection} to provide type-safe storage of
 * criteria parameters for test execution time analysis and suite distribution.
 */
class CriteriaArrayCollection extends ArrayCollection
{
}
