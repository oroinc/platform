<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowExclusionProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowExclusionProviderTest extends TestCase
{
    private ClassMetadata&MockObject $metadata;
    private WorkflowExclusionProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->provider = new WorkflowExclusionProvider();
    }

    public function testIsIgnoredFieldAndUnknownField(): void
    {
        $this->assertFalse($this->provider->isIgnoredField($this->metadata, 'unknown_field'));
    }

    public function testIsIgnoredFieldAndItemsRelation(): void
    {
        $this->assertTrue(
            $this->provider->isIgnoredField($this->metadata, WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsIgnoredFieldAndStepsRelation(): void
    {
        $this->assertTrue(
            $this->provider->isIgnoredField($this->metadata, WorkflowVirtualRelationProvider::STEPS_RELATION_NAME)
        );
    }
}
