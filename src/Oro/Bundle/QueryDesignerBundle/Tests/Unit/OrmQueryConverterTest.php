<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit;

class OrmQueryConverterTest extends \PHPUnit_Framework_TestCase
{
    protected function getVirtualFieldProvider(array $config = [])
    {
        $provider = $this->getMock('Oro\Bundle\QueryDesignerBundle\QueryDesigner\VirtualFieldProviderInterface');
        $provider->expects($this->any())
            ->method('getMainEntityAlias')
            ->will($this->returnValue('entity'));
        $provider->expects($this->any())
            ->method('isVirtualField')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use (&$config) {
                        $result = false;
                        foreach ($config as $item) {
                            if ($item[0] === $className && $item[1] === $fieldName) {
                                $result = true;
                                break;
                            }
                        }

                        return $result;
                    }
                )
            );
        $provider->expects($this->any())
            ->method('getVirtualFieldQuery')
            ->will(
                $this->returnCallback(
                    function ($className, $fieldName) use (&$config) {
                        $result = [];
                        foreach ($config as $item) {
                            if ($item[0] === $className && $item[1] === $fieldName) {
                                $result = $item[2];
                                break;
                            }
                        }

                        return $result;
                    }
                )
            );

        return $provider;
    }

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

    protected function getDoctrine(array $config = [], array $identifiersConfig = [])
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
            $associationMap = [];
            foreach ($fields as $fieldName => $fieldType) {
                if (!is_array($fieldType)) {
                    $typeMap[] = [$fieldName, $fieldType];
                } else {
                    $associationMap[] = [$fieldName, ['joinColumns' => [$fieldType]]];
                }
            }

            $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
                ->disableOriginalConstructor()
                ->getMock();
            $metadata->expects($this->any())
                ->method('getTypeOfField')
                ->will($this->returnValueMap($typeMap));

            if (!empty($identifiersConfig[$entity])) {
                $metadata->expects($this->any())->method('getIdentifier')
                    ->will($this->returnValue($identifiersConfig[$entity]));
            }
            if (!empty($associationMap)) {
                $metadata->expects($this->any())
                    ->method('getAssociationMapping')
                    ->will($this->returnValueMap($associationMap));
            }

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
