<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowExclusionProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class WorkflowExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $metadata;

    /** @var WorkflowExclusionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(ClassMetadata::class);

        $this->provider = new WorkflowExclusionProvider();
    }

    public function testIsIgnoredFieldAndUnknownField()
    {
        $this->assertFalse($this->provider->isIgnoredField($this->metadata, 'unknown_field'));
    }

    public function testIsIgnoredFieldAndItemsRelation()
    {
        $this->assertTrue(
            $this->provider->isIgnoredField($this->metadata, WorkflowVirtualRelationProvider::ITEMS_RELATION_NAME)
        );
    }

    public function testIsIgnoredFieldAndStepsRelation()
    {
        $this->assertTrue(
            $this->provider->isIgnoredField($this->metadata, WorkflowVirtualRelationProvider::STEPS_RELATION_NAME)
        );
    }
}
