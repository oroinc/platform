<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Options;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class OptionsContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var OptionsContext */
    private $context;

    protected function setUp()
    {
        $this->context = new OptionsContext(
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );
    }

    public function testActionType()
    {
        $this->context->setActionType('item');
        self::assertEquals('item', $this->context->getActionType());
        self::assertEquals('item', $this->context->get('actionType'));

        $this->context->setActionType('list');
        self::assertEquals('list', $this->context->getActionType());
        self::assertEquals('list', $this->context->get('actionType'));

        $this->context->setActionType('subresource');
        self::assertEquals('subresource', $this->context->getActionType());
        self::assertEquals('subresource', $this->context->get('actionType'));

        $this->context->setActionType('relationship');
        self::assertEquals('relationship', $this->context->getActionType());
        self::assertEquals('relationship', $this->context->get('actionType'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage The action type is not set yet.
     */
    public function testGetActionTypeWhenItDoesNotSet()
    {
        $this->context->getActionType();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The action type must be one of item, list, subresource, relationship. Given: another.
     */
    public function testSetInvalidActionType()
    {
        $this->context->setActionType('another');
    }

    public function testId()
    {
        self::assertNull($this->context->getId());

        $this->context->setId(123);
        self::assertSame(123, $this->context->getId());
        self::assertSame(123, $this->context->get('id'));
    }
}
