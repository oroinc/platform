<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\OrganizationBundle\Validator\Constraints\OrganizationUniqueEntity;

class OrganizationUniqueEntityTest extends \PHPUnit\Framework\TestCase
{
    public function testService()
    {
        $testClass = new OrganizationUniqueEntity(['fields' => 'test']);
        $this->assertEquals('organization_unique', $testClass->service);
    }
}
