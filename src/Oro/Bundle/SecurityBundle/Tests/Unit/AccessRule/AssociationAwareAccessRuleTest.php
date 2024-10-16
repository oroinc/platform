<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\AccessRule;

use Oro\Bundle\SecurityBundle\AccessRule\AssociationAwareAccessRule;
use Oro\Bundle\SecurityBundle\AccessRule\Criteria;
use Oro\Bundle\SecurityBundle\AccessRule\Expr\Association;

class AssociationAwareAccessRuleTest extends \PHPUnit\Framework\TestCase
{
    private AssociationAwareAccessRule $accessRule;

    #[\Override]
    protected function setUp(): void
    {
        $this->accessRule = new AssociationAwareAccessRule('association');
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->accessRule->isApplicable($this->createMock(Criteria::class)));
    }

    public function testProcess()
    {
        $criteria = new Criteria('ORM', \stdClass::class, 'test');
        $this->accessRule->process($criteria);

        $this->assertEquals(new Association('association'), $criteria->getExpression());
    }
}
