<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Grid;

use Oro\Bundle\EntityBundle\Grid\ExtendExtension;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class ExtendExtensionTest extends \PHPUnit_Framework_TestCase
{
    const EXTEND_ENTITY_FULL  = 'OroCRM\Bundle\ContactBundle\Entity\Contact';
    const EXTEND_ENTITY_SHORT = 'OroCRMContactBundle:Contact';
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $datagridConfig;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Class 'fake class' does not exist
     */
    public function testVisitDatasourceThrowAnException()
    {
        $entityName = 'fake class';

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->will($this->throwException(new \Exception('Class \'' . $entityName . '\' does not exist')));

        $this->configManager->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->datagridConfig->expects($this->once())
            ->method('offsetGetByPath')
            ->with(ExtendExtension::EXTEND_ENTITY_CONFIG_PATH)
            ->will($this->returnValue($entityName));

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dataSource = new OrmDatasource($entityManager, $eventDispatcher);

        $extendExtension = new ExtendExtension($this->configManager);
        $extendExtension->visitDatasource($this->datagridConfig, $dataSource);
    }

    /**
     * @dataProvider visitDatasourceDataProvider
     * @param $entityName
     * @param $hasConfig
     * @param $fieldIds
     */
    public function testVisitDatasource($entityName, $hasConfig, $fieldIds)
    {
        $alias      = 'c';
        $countField = count($fieldIds);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue(self::EXTEND_ENTITY_FULL));

        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($entityName)
            ->will($this->returnValue($metadata));

        $this->configManager->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $from = $this->getMockBuilder('Doctrine\ORM\Query\Expr\From')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        if ($countField && $hasConfig) {
            $from->expects($this->once())
                ->method('getAlias')
                ->will($this->returnValue($alias));
            $from->expects($this->once())
                ->method('getFrom')
                ->will($this->returnValue(self::EXTEND_ENTITY_FULL));

            $queryBuilder->expects($this->once())
                ->method('getDQLPart')
                ->with('from')
                ->will($this->returnValue(array($from)));
            $queryBuilder->expects($this->exactly($countField))
                ->method('addSelect')
                ->will($this->returnSelf());
            $this->datagridConfig->expects($this->exactly($countField * 2))
                ->method('offsetSetByPath')
                ->will($this->returnSelf());
        } else {
            $from->expects($this->never())->method('getAlias');
            $from->expects($this->never())->method('getFrom');
            $queryBuilder->expects($this->never())->method('getDQLPart');
            $queryBuilder->expects($this->never())->method('addSelect');
            $this->datagridConfig->expects($this->never())->method('offsetSetByPath');
        }

        $dataSource = new OrmDatasource($entityManager, $eventDispatcher);
        $dataSource->setQueryBuilder($queryBuilder);

        $this->mockGetDynamicFields($hasConfig, $fieldIds);

        $this->datagridConfig->expects($this->once())
            ->method('offsetGetByPath')
            ->with(ExtendExtension::EXTEND_ENTITY_CONFIG_PATH)
            ->will($this->returnValue($entityName));

        $extendExtension = new ExtendExtension($this->configManager);
        $extendExtension->visitDatasource($this->datagridConfig, $dataSource);
    }

    /**
     * @return array
     */
    public function visitDatasourceDataProvider()
    {
        return array(
            'config has not entity name; short name; one field' => array(
                'entityName'      => self::EXTEND_ENTITY_SHORT,
                'hasConfig'       => false,
                'dynamicFieldIds' => $this->generateFieldIds()
            ),
            'config has entity name; short name; one field' => array(
                'entityName'      => self::EXTEND_ENTITY_SHORT,
                'hasConfig'       => true,
                'dynamicFieldIds' => $this->generateFieldIds()
            ),
            'config has not entity name; full name: one field' => array(
                'entityName'      => self::EXTEND_ENTITY_FULL,
                'hasConfig'       => false,
                'dynamicFieldIds' => $this->generateFieldIds()
            ),
            'config has entity name; full name; one field' => array(
                'entityName'      => self::EXTEND_ENTITY_FULL,
                'hasConfig'       => true,
                'dynamicFieldIds' => $this->generateFieldIds()
            ),
            'config has entity name; full name: five field' => array(
                'entityName'      => self::EXTEND_ENTITY_FULL,
                'hasConfig'       => true,
                'dynamicFieldIds' => $this->generateFieldIds(5)
            ),
            'config has entity name; shot name: five field' => array(
                'entityName'      => self::EXTEND_ENTITY_FULL,
                'hasConfig'       => true,
                'dynamicFieldIds' => $this->generateFieldIds(5)
            ),
        );
    }

    protected function mockGetDynamicFields($hasConfig, $fieldIds)
    {
        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with(self::EXTEND_ENTITY_FULL)
            ->will($this->returnValue($hasConfig));

        if (!$hasConfig) {
            $this->configManager->expects($this->never())
                ->method('getProvider');
        } else {
            $callCount = count($fieldIds);
            $entityProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();
            $entityProvider->expects($this->once())
                ->method('getIds')
                ->with(self::EXTEND_ENTITY_FULL)
                ->will($this->returnValue($fieldIds));

            $extendConfig = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Config')
                ->disableOriginalConstructor()
                ->getMock();
            $extendConfig->expects($this->any())
                ->method('is')
                ->will(
                    $this->returnCallback(
                        function ($code, $value = true) {
                            switch($code) {
                                case 'owner':
                                    return ExtendScope::OWNER_CUSTOM == $value;
                                case 'state':
                                    return !(ExtendScope::STATE_NEW == $value);
                                case 'is_deleted':
                                    return !(true === $value);
                                case 'is_visible':
                                    return true === $value;
                            }
                            return null;
                        }
                    )
                );

            $extendProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();
            $extendProvider->expects($this->exactly($callCount))
                ->method('getConfigById')
                ->will($this->returnValue($extendConfig));

            $datagridProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
                ->disableOriginalConstructor()
                ->getMock();
            $datagridProvider->expects($this->exactly($callCount))
                ->method('getConfigById')
                ->will($this->returnValue($extendConfig));

            $this->configManager->expects($this->any())
                ->method('getProvider')
                ->will(
                    $this->returnCallback(
                        function ($scope) use ($entityProvider, $extendProvider, $datagridProvider) {
                            switch($scope) {
                                case 'entity':
                                    return $entityProvider;
                                case 'extend':
                                    return $extendProvider;
                                case 'datagrid':
                                    return $datagridProvider;
                            }
                            return null;
                        }
                    )
                );
        }
    }

    protected function generateFieldIds($count = 1)
    {
        $fieldIds = array();
        for ($i = 0; $i < $count; $i++) {
            $fieldIds[] = new FieldConfigId('entity', self::EXTEND_ENTITY_FULL, 'name-' . $i, 'fieldType' . $i);
        }
        return $fieldIds;
    }
}
