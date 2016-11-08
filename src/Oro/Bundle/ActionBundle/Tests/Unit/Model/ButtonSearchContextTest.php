<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonSearchContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  ButtonSearchContext */
    protected $buttonSearchContext;
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buttonSearchContext = new ButtonSearchContext();
    }

    public function testProperties()
    {
        $properties = [
            ['entityClass', 'Test/Class'],
            ['entityId', 1],
            ['routeName', 'test_route'],
            ['gridName', 'test_grid'],
            ['referrer', 'test_ref'],
            ['group', 'test_group']
        ];

        $this->assertPropertyAccessors($this->buttonSearchContext, $properties);
    }

    public function testGetSetEntity()
    {
        $this->buttonSearchContext->setEntity('Class', 10);
        $this->assertSame('Class', $this->buttonSearchContext->getEntityClass());
        $this->assertSame(10, $this->buttonSearchContext->getEntityId());
    }
}
