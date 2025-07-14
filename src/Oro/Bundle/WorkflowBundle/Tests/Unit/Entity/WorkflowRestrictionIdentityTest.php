<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestriction;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowRestrictionIdentity;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class WorkflowRestrictionIdentityTest extends TestCase
{
    private WorkflowRestrictionIdentity $identity;

    #[\Override]
    protected function setUp(): void
    {
        $this->identity = new WorkflowRestrictionIdentity();
    }

    public function testGetId(): void
    {
        $this->assertNull($this->identity->getId());

        $value = 1;
        ReflectionUtil::setId($this->identity, $value);
        $this->assertSame($value, $this->identity->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($this->identity, $property, $value);
        $this->assertEquals($value, $accessor->getValue($this->identity, $property));
    }

    public function propertiesDataProvider(): array
    {
        return [
            ['restriction', new WorkflowRestriction()],
            ['entityId', 123],
            ['workflowItem', new WorkflowItem()],
        ];
    }
}
