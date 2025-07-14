<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Metadata;

use Oro\Bundle\SecurityBundle\Metadata\ActionSecurityMetadata;
use PHPUnit\Framework\TestCase;

class ActionSecurityMetadataTest extends TestCase
{
    private ActionSecurityMetadata $metadata;

    #[\Override]
    protected function setUp(): void
    {
        $this->metadata = new ActionSecurityMetadata(
            'SomeName',
            'SomeGroup',
            'SomeLabel',
            'SomeDescription',
            'SomeCategory'
        );
    }

    public function testGetters(): void
    {
        $this->assertEquals('SomeName', $this->metadata->getClassName());
        $this->assertEquals('SomeGroup', $this->metadata->getGroup());
        $this->assertEquals('SomeLabel', $this->metadata->getLabel());
        $this->assertEquals('SomeDescription', $this->metadata->getDescription());
        $this->assertEquals('SomeCategory', $this->metadata->getCategory());
    }

    public function testSerialize(): void
    {
        $data = serialize($this->metadata);
        $emptyMetadata = unserialize($data);
        $this->assertEquals('SomeName', $emptyMetadata->getClassName());
        $this->assertEquals('SomeGroup', $emptyMetadata->getGroup());
        $this->assertEquals('SomeLabel', $this->metadata->getLabel());
        $this->assertEquals('SomeDescription', $this->metadata->getDescription());
        $this->assertEquals('SomeCategory', $this->metadata->getCategory());
    }
}
