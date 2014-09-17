<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit;

use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\QueryDesignerBundle\Grid\DatagridConfigurationBuilder;
use Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\QueryDesignerModel;

abstract class OrmQueryConverterTest extends \PHPUnit_Framework_TestCase
{
    protected function getVirtualFieldProvider(array $config = [])
    {
        $provider = $this->getMock('Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface');
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

    /**
     * @param array $config            Example:
     *                                 'Test\Entity1' => array
     *                                 .    'column1'   => 'string',
     *                                 .    'relation1' => ['nullable' => true],
     *                                 'Test\Entity2' => array
     *                                 .    'column1' => 'integer',
     * @param array $identifiersConfig Example:
     *                                 'Test\Entity1' => ['id'],
     *                                 'Test\Entity2' => ['id'],
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
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

    /**
     * @param QueryDesignerModel                            $model
     * @param \PHPUnit_Framework_MockObject_MockObject|null $doctrine
     * @param \PHPUnit_Framework_MockObject_MockObject|null $functionProvider
     * @param \PHPUnit_Framework_MockObject_MockObject|null $virtualFieldProvider
     * @param array                                         $guessers
     *
     * @return DatagridConfigurationBuilder
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function createDatagridConfigurationBuilder(
        QueryDesignerModel $model,
        $doctrine = null,
        $functionProvider = null,
        $virtualFieldProvider = null,
        array $guessers = []
    ) {
        $builder = new DatagridConfigurationBuilder(
            $functionProvider ? : $this->getFunctionProvider(),
            $virtualFieldProvider ? : $this->getVirtualFieldProvider(),
            $doctrine ? : $this->getDoctrine(),
            new DatagridGuesserMock($guessers)
        );

        $builder->setGridName('test_grid');
        $builder->setSource($model);

        return $builder;
    }
}
