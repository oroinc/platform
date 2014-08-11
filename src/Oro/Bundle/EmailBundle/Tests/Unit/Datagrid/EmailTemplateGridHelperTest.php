<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\EmailBundle\Datagrid\EmailTemplateGridHelper;

class EmailTemplateGridHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EmailTemplateGridHelper */
    protected $helper;

    protected function setUp()
    {
        $this->entityProvider = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->helper = new EmailTemplateGridHelper(
            $this->entityProvider,
            $translator
        );
    }

    public function testGetEntityNames()
    {
        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->will(
                $this->returnValue(
                    [
                        ['name' => 'TestEntity1', 'label' => 'entity1_label'],
                        ['name' => 'TestEntity2', 'label' => 'entity2_label'],
                    ]
                )
            );

        $result = $this->helper->getEntityNames();
        $this->assertSame(
            [
                '_empty_'     => 'oro.email.datagrid.emailtemplate.filter.entityName.empty',
                'TestEntity1' => 'entity1_label',
                'TestEntity2' => 'entity2_label',
            ],
            $result
        );
    }
}
