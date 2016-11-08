<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ButtonContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  ButtonContext */
    protected $buttonContext;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->buttonContext = new ButtonContext();
    }

    public function testGetSetButtonContext()
    {
        $context = [
            ['routeName', 'test_route'],
            ['datagridName', 'datagrid'],
            ['group', 'test_group'],
            ['executionUrl', 'test_url1'],
            ['dialogUrl', 'test_url2'],
            ['enabled', true],
            ['unavailableHidden', true]
        ];

        $this->assertPropertyAccessors($this->buttonContext, $context);
    }

    public function testSetGetEntity()
    {
        $this->buttonContext->setEntity('Class', 10);
        $this->assertSame('Class', $this->buttonContext->getEntityClass());
        $this->assertSame(10, $this->buttonContext->getEntityId());
    }
}
