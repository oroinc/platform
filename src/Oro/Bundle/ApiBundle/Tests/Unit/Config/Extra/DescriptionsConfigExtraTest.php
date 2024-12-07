<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\DescriptionsConfigExtra;

class DescriptionsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var DescriptionsConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new DescriptionsConfigExtra();
    }

    public function testDocumentationAction(): void
    {
        self::assertNull($this->extra->getDocumentationAction());

        $documentationAction = 'some_action';
        $this->extra->setDocumentationAction($documentationAction);
        self::assertEquals($documentationAction, $this->extra->getDocumentationAction());

        $this->extra->setDocumentationAction(null);
        self::assertNull($this->extra->getDocumentationAction());
    }

    public function testGetName(): void
    {
        self::assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getName());
    }

    public function testIsPropagable(): void
    {
        self::assertFalse($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(DescriptionsConfigExtra::NAME, $this->extra->getCacheKeyPart());
    }

    public function testCacheKeyPartWithDocumentationAction(): void
    {
        $this->extra->setDocumentationAction('some_action');
        self::assertEquals(DescriptionsConfigExtra::NAME . ':some_action', $this->extra->getCacheKeyPart());
    }
}
