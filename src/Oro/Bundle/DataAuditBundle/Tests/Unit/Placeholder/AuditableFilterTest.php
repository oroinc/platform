<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DataAuditBundle\Placeholder\AuditableFilter;

class AuditableFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AuditableFilter
     */
    protected $filter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new AuditableFilter($this->configProvider);
    }

    /**
     * @dataProvider isAuditableDataProvider
     */
    public function testIsEntityAuditable($data, $expected)
    {
        if (isset($expected['class'])) {
            $this->configProvider->expects($this->once())
                ->method('hasConfig')
                ->with($expected['class'])
                ->will($this->returnValue($expected['has_config_result']));
            $auditable = $this->getMock('\Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
            $auditable->expects($this->once())
                ->method('is')
                ->with('auditable')
                ->will($this->returnValue($expected['is_auditable_result']));
            $this->configProvider->expects($this->once())
                ->method('getConfig')
                ->with($expected['class'])
                ->will($this->returnValue($auditable));
        }

        $result = $this->filter->isEntityAuditable($data['entity'], $data['entity_class'], $data['show']);
        $this->assertEquals($expected['result'], $result);
    }

    public function isAuditableDataProvider()
    {
        $expectedClass = 'testClassName';
        $entityConfigModel = $this->getMock('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel');
        $entityConfigModel->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($expectedClass));

        return array(
            'return false if entity no object' => array(
                'data' => array(
                    'entity'       => array('id' => 1),
                    'entity_class' => '',
                    'show'         => false
                ),
                'expected' => array(
                    'result'    => false
                )
            ),
            'return true if show == true' => array(
                'data' => array(
                    'entity'       => array('id' => 1),
                    'entity_class' => '',
                    'show'         => true
                ),
                'expected' => array(
                    'result'    => true
                )
            ),
            'return true if class is auditable' => array(
                'data' => array(
                    'entity'       => $this->getMock('\StdClass'),
                    'entity_class' => 'test_class_name',
                    'show'         => false
                ),
                'expected' => array(
                    'result'          => true,
                    'class'           => 'test\class\name',
                    'has_config_result' => true,
                    'is_auditable_result' => true
                )
            ),
            'return true if class is auditable but class name is empty' => array(
                'data' => array(
                    'entity'       => $this->getMock('\StdClass', array(), array(), $expectedClass),
                    'entity_class' => '',
                    'show'         => false
                ),
                'expected' => array(
                    'result'          => true,
                    'class'           => 'testClassName',
                    'has_config_result' => true,
                    'is_auditable_result' => true
                )
            ),
            'return true if class is entity config model and class name is empty' => array(
                'data' => array(
                    'entity'       => $entityConfigModel,
                    'entity_class' => '',
                    'show'         => false
                ),
                'expected' => array(
                    'result'          => true,
                    'class'           => $expectedClass,
                    'has_config_result' => true,
                    'is_auditable_result' => true
                )
            )
        );
    }
}
