<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Duplicator\Extension;

use DeepCopy\Matcher\PropertyTypeMatcher;
use Oro\Bundle\DraftBundle\Duplicator\Extension\DataTimeExtension;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Tests\Unit\Stub\DraftableEntityStub;
use Oro\Component\Duplicator\Filter\ShallowCopyFilter;
use Oro\Component\Testing\Unit\EntityTrait;

class DataTimeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DataTimeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new DataTimeExtension();
    }

    public function testGetFilter(): void
    {
        $this->assertEquals(new ShallowCopyFilter(), $this->extension->getFilter());
    }
    
    public function testGetMatcher(): void
    {
        $this->assertEquals(new PropertyTypeMatcher(\DateTime::class), $this->extension->getMatcher());
    }

    public function testIsSupport(): void
    {
        /** @var DraftableInterface $source */
        $source = $this->getEntity(DraftableEntityStub::class);
        $this->assertTrue($this->extension->isSupport($source));
    }
}
