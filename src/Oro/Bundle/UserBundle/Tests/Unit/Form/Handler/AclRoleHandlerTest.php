<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

class AclRoleHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AclRoleHandler
     */
    protected $handler;

    protected function setUp()
    {
        $factory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new AclRoleHandler($factory, []);
    }

    public function testAddExtensionFilter()
    {
        $this->assertAttributeEmpty('extensionFilters', $this->handler);

        $actionKey = 'action';
        $entityKey = 'entity';

        $defaultGroup = 'default';

        $this->handler->addExtensionFilter($actionKey, $defaultGroup);
        $this->handler->addExtensionFilter($entityKey, $defaultGroup);

        $expectedFilters = [
            $actionKey => [$defaultGroup],
            $entityKey => [$defaultGroup],
        ];
        $this->assertAttributeEquals($expectedFilters, 'extensionFilters', $this->handler);

        // each group added only once
        $this->handler->addExtensionFilter($actionKey, $defaultGroup);
        $this->handler->addExtensionFilter($entityKey, $defaultGroup);

        $this->assertAttributeEquals($expectedFilters, 'extensionFilters', $this->handler);
    }
}
