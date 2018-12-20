<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;

class NullFilterValueAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var NullFilterValueAccessor */
    private $nullFilterValueAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->nullFilterValueAccessor = new NullFilterValueAccessor();
    }

    public function testActions()
    {
        self::assertInstanceOf(FilterValueAccessorInterface::class, $this->nullFilterValueAccessor);

        self::assertNull($this->nullFilterValueAccessor->get('key'));
        self::assertFalse($this->nullFilterValueAccessor->has('key'));
        self::assertFalse($this->nullFilterValueAccessor->has('key'));
        self::assertEmpty($this->nullFilterValueAccessor->getAll());
        self::assertEmpty($this->nullFilterValueAccessor->getGroup('group'));
    }

    public function testDefaultGroupName()
    {
        self::assertNull($this->nullFilterValueAccessor->getDefaultGroupName());

        $this->nullFilterValueAccessor->setDefaultGroupName('filter');
        self::assertNull($this->nullFilterValueAccessor->getDefaultGroupName());
    }

    public function testGetQueryString()
    {
        self::assertSame('', $this->nullFilterValueAccessor->getQueryString());
    }
}
