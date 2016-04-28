<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

abstract class AbstractExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\OperationManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridConfigurationHelper = new GridConfigurationHelper($this->entityClassResolver);
    }

    protected function tearDown()
    {
        unset($this->manager, $this->entityClassResolver, $this->gridConfigurationHelper);
    }

    /**
     * @param string $name
     * @param bool $isAvailable
     * @param array $definitionParams
     *
     * @return Operation|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOperation($name = 'test_operation', $isAvailable = true, array $definitionParams = [])
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OperationDefinition $definition */
        $definition = $this->getMock('Oro\Bundle\ActionBundle\Model\OperationDefinition');

        foreach ($definitionParams as $method => $params) {
            $definition->expects($this->any())->method($method)->willReturn($params);
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|Operation $operation */
        $operation = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->any())->method('getDefinition')->willReturn($definition);
        $operation->expects($this->any())->method('getName')->willReturn($name);
        $operation->expects($this->any())->method('isAvailable')->willReturn($isAvailable);

        return $operation;
    }
}
