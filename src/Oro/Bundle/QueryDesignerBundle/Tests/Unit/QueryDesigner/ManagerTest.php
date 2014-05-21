<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testIsIgnoredField()
    {
        $metadata  = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'testField';
        $className = 'Test\Entity';

        $manager = $this->getManager(
            [
                'exclude' => [
                    ['type'       => 'integer'],
                    ['query_type' => 'segment'],
                    ['entity'     => $className, $fieldName],
                    ['entity'     => 'Never\Existing\EntityNeverChecked'],
            ]
            ]
        );

        $reflectionClassMock = $this->getMockBuilder('\ReflectionClass')
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionClassMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($className));

        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue($reflectionClassMock));

        $metadata->expects($this->at(1))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('string'));

        $metadata->expects($this->at(2))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('integer'));

        $metadata->expects($this->at(2))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('boolean'));

        $result = $manager->isIgnoredField($metadata, $fieldName, 'report');
        $this->assertTrue($result);
    }

    public function testIsIgnoredAssosiation()
    {
        $metadata  = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldName = 'testRelation';
        $className = 'Test\Entity';

        $manager = $this->getManager(
            [
                'exclude' => [
                    ['type'       => 'integer'],
                    ['query_type' => 'segment'],
                    ['entity'     => $className, $fieldName],
                    ['entity'     => 'Never\Existing\EntityNeverChecked'],
                ]
            ]
        );

        $reflectionClassMock = $this->getMockBuilder('\ReflectionClass')
            ->disableOriginalConstructor()
            ->getMock();
        $reflectionClassMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($className));

        $metadata->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue($reflectionClassMock));

        $metadata->expects($this->at(1))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('string'));

        $metadata->expects($this->at(2))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('integer'));

        $metadata->expects($this->at(2))
            ->method('getTypeOfField')
            ->with($fieldName)
            ->will($this->returnValue('boolean'));

        $result = $manager->isIgnoredField($metadata, $fieldName, 'report');
        $this->assertTrue($result);
    }

    /**
     * @param $config
     *
     * @return Manager
     */
    protected function getManager($config)
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

        return new Manager(
            $config,
            $resolverMock,
            $hierarchyProviderMock,
            $translator
        );
    }
}
