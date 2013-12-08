<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit;

class OrmQueryConverterTest extends \PHPUnit_Framework_TestCase
{
    protected function getFunctionProvider(array $config = [])
    {
        $provider = $this->getMock('Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface');
        if (empty($config)) {
            $provider->expects($this->never())
                ->method('getFunction');
        } else {
            $provider->expects($this->any())
                ->method('getFunction')
                ->will($this->returnValueMap($config));
        }

        return $provider;
    }

    protected function getDoctrine(array $config = [])
    {
        $doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $emMap = [];
        foreach ($config as $entity => $fields) {
            $em      = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();
            $emMap[] = [$entity, $em];

            $typeMap = [];
            foreach ($fields as $fieldName => $fieldType) {
                $typeMap[] = [$fieldName, $fieldType];
            }

            $metadata = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
            $metadata->expects($this->any())
                ->method('getTypeOfField')
                ->will($this->returnValueMap($typeMap));

            $em->expects($this->any())
                ->method('getClassMetadata')
                ->with($entity)
                ->will($this->returnValue($metadata));
        }

        if (!empty($emMap)) {
            $doctrine->expects($this->any())
                ->method('getManagerForClass')
                ->will($this->returnValueMap($emMap));
        }

        return $doctrine;
    }
}
