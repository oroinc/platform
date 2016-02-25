<?php

namespace Oro\Component\Testing\Unit;

/**
 * @deprecated Use EntityTestCaseTrait instead of inheriting from this class
 */
abstract class EntityTestCase extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
}
