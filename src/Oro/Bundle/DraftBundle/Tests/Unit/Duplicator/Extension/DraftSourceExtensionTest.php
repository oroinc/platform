<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Matcher\PropertyNameMatcher;
use Oro\Bundle\DraftBundle\Duplicator\DraftContext;
use Oro\Bundle\DraftBundle\Duplicator\Extension\DraftSourceExtension;
use Oro\Bundle\DraftBundle\Duplicator\Filter\SourceFilter;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;

class DraftSourceExtensionTest extends TestCase
{
    use EntityTrait;

    private DraftSourceExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new DraftSourceExtension();
    }

    public function testGetFilter(): void
    {
        $context = new DraftContext();
        $source = $this->getEntity(DraftableEntityStub::class);
        $context->offsetSet('source', $source);
        $this->extension->setContext($context);

        $this->assertEquals(new SourceFilter($source), $this->extension->getFilter());
    }

    public function testGetMatcher(): void
    {
        $this->assertEquals(new PropertyNameMatcher('draftSource'), $this->extension->getMatcher());
    }

    public function testIsSupport(): void
    {
        $source = $this->getEntity(DraftableEntityStub::class);
        $this->assertTrue($this->extension->isSupport($source));
    }
}
