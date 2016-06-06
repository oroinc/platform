<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\NullFilterValueAccessor;

class NullFilterValueAccessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var NullFilterValueAccessor */
    protected $nullFilterValueAccessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->nullFilterValueAccessor = new NullFilterValueAccessor();
    }

    public function testActions()
    {
        $this->assertInstanceOf(
            'Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface',
            $this->nullFilterValueAccessor
        );

        $this->assertNull($this->nullFilterValueAccessor->get('key'));
        $this->assertFalse($this->nullFilterValueAccessor->has('key'));
        $this->assertEmpty($this->nullFilterValueAccessor->getAll());
        $this->assertEmpty($this->nullFilterValueAccessor->getGroup('group'));
    }
}
