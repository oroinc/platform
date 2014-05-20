<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Manager */
    protected $manager;

    public function setUp()
    {
        $resolverMock = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\ConfigurationResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolverMock->expects($this->once())
            ->method('resolve');

        $hierarchyProviderMock = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $virtualFieldsProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\VirtualFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $virtualFieldsProvider->expects($this->once())
            ->method('getVirtualFieldsWithHierarchy')
            ->will($this->returnValue([]));

        $this->manager = new Manager(
            [
                'exclude' => []
            ],
            $resolverMock,
            $hierarchyProviderMock,
            $translator,
            $virtualFieldsProvider
        );
    }

    public function testIsIgnoredField()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'test';

        $result = $this->manager->isIgnoredField($metadata, $fieldName, 'report');
        $this->assertFalse($result);
    }
} 