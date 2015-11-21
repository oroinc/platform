<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Model;

use Oro\Bundle\SecurityBundle\Form\Model\Factory;
use Oro\Bundle\SecurityBundle\Form\Model\Share;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Factory */
    protected $factory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->factory = new Factory();
    }

    public function testGetShare()
    {
        $this->assertTrue($this->factory->getShare() instanceof Share);
    }
}
