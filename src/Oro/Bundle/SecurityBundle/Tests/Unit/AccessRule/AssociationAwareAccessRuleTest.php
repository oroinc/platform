<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AssociationAwareAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;
use PHPUnit\Framework\TestCase;

class AssociationAwareAccessRuleTest extends TestCase
{
    private AssociationAwareAccessRule $accessRule;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessRule = new AssociationAwareAccessRule('association');
    }

    public function testIsApplicable(): void
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess(): void
    {
        $criteria = new Criteria('ORM', \stdClass::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(new Association('association'), $criteria->getExpression());
    }
}
