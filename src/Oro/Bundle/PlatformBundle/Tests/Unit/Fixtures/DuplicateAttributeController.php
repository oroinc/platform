<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

/**
 * Test controller with duplicate non-array attributes for error testing
 */
#[TestAttribute('first_value', true)]
#[TestAttribute('second_value', false)]
class DuplicateAttributeController
{
    public function duplicateAttributeAction()
    {
        return 'duplicate attributes';
    }
}
