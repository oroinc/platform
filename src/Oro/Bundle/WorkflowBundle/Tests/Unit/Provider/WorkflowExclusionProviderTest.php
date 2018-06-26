<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowExclusionProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowVirtualRelationProvider;

class WorkflowExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var WorkflowExclusionProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

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
