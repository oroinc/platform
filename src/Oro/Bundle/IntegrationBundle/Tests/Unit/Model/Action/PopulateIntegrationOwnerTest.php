<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\IntegrationBundle\Model\Action\PopulateIntegrationOwner;

use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Model\ContextAccessor;

class PopulateIntegrationOwnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $defaultOwnerHelper;

    /**
     * @var ActionInterface
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->defaultOwnerHelper = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new PopulateIntegrationOwner($this->contextAccessor, $this->defaultOwnerHelper);
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @dataProvider invalidOptionsDataProvider
     * @param array $options
     */
    public function testInitializeExceptions(array $options)
    {
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function invalidOptionsDataProvider()
    {
        return [
            [[]],
            [[1, 2]],
            [['attribute' => 'a']],
            [['integration' => 'a']],
        ];
    }

    public function testInitialize()
    {
        $attribute = 'a';
        $integration = 'b';

        $options = ['attribute' => $attribute, 'integration' => $integration];
        $this->assertSame($this->action, $this->action->initialize($options));

        $this->assertAttributeEquals($attribute, 'attribute', $this->action);
        $this->assertAttributeEquals($integration, 'integration', $this->action);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Action "populate_channel_owner" expects an entity in parameter "attribute", string is given.
     */
    // @codingStandardsIgnoreEnd
    public function testExecuteIncorrectEntity()
    {
        $context = new \stdClass();
        $context->attr = 'test';
        $context->integration = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultOwnerHelper->expects($this->never())
            ->method($this->anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     * @expectedExceptionMessage Action "populate_channel_owner" expects Oro\Bundle\IntegrationBundle\Entity\Channel in parameter "integration", stdClass is given.
     */
    // @codingStandardsIgnoreEnd
    public function testExecuteIncorrectIntegration()
    {
        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = new \stdClass();

        $this->defaultOwnerHelper->expects($this->never())
            ->method($this->anything());

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecute()
    {
        $context = new \stdClass();
        $context->attr = new \stdClass();
        $context->integration = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $this->defaultOwnerHelper->expects($this->once())
            ->method('populateChannelOwner')
            ->with($context->attr, $context->integration);

        $options = ['attribute' => new PropertyPath('attr'), 'integration' => new PropertyPath('integration')];
        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
