<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\EmailBundle\Datagrid\EmailTemplateGridHelper;

class EmailTemplateGridHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
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
                'oro.email.datagrid.emailtemplate.filter.entityName.empty' => '_empty_',
                'entity1_label' => 'TestEntity1',
                'entity2_label' => 'TestEntity2',
            ],
            $result
        );
    }
}
