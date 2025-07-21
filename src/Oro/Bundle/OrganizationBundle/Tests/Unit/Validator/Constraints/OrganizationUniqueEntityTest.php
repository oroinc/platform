<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\OrganizationUniqueEntity;
use PHPUnit\Framework\TestCase;

class OrganizationUniqueEntityTest extends TestCase
{
    public function testService(): void
    {
        $testClass = new OrganizationUniqueEntity(['fields' => 'test']);
        $this->assertEquals('organization_unique', $testClass->service);
    }
}
