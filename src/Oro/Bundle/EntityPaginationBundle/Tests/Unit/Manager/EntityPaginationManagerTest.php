<?php

namespace Oro\Bundle\EntityPaginationBundle\Tests\Unit\Navigation;

use Oro\Bundle\EntityPaginationBundle\Manager\EntityPaginationManager;

class EntityPaginationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var EntityPaginationManager */
    protected $entityPaginationManager;

    /** @var \stdClass */
    protected $entity;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityPaginationManager = new EntityPaginationManager($this->configManager);
        $this->entity = new \stdClass();
    }
    
    /**
     * @param mixed $source
     * @param bool $expected
     * @dataProvider isEnabledDataProvider
     */
    public function testIsEnabled($source, $expected)
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.enabled')
            ->will($this->returnValue($source));
    
        $storage = new EntityPaginationManager($configManager);
        $this->assertSame($expected, $storage->isEnabled());
    }

    /**
     * @return array
     */
    public function isEnabledDataProvider()
    {
        return [
            'string true' => [
                'source'   => '1',
                'expected' => true,
            ],
            'string false' => [
                'source'   => '0',
                'expected' => false,
            ],
            'boolean true' => [
                'source'   => true,
                'expected' => true,
            ],
            'boolean false' => [
                'source'   => false,
                'expected' => false,
            ],
            'null' => [
                'source'   => null,
                'expected' => false,
            ],
        ];
    }

    public function testGetLimit()
    {
        $limit = 200;
    
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_entity_pagination.limit')
            ->will($this->returnValue($limit));
    
        $this->assertEquals($limit, $this->entityPaginationManager->getLimit());
    }

    /**
     * @expectedException \LogicException
     */
    public function testGetPermissionException()
    {
        $this->entityPaginationManager->getPermission('wrong_scope_name');
    }
}
