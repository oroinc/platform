<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Datagrid;

use Oro\Bundle\EmailBundle\Datagrid\EmailTemplateGridHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateGridHelperTest extends TestCase
{
    private EntityProvider&MockObject $entityProvider;
    private EmailTemplateGridHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityProvider = $this->createMock(EntityProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->helper = new EmailTemplateGridHelper(
            $this->entityProvider,
            $translator
        );
    }

    public function testGetEntityNames(): void
    {
        $this->entityProvider->expects($this->once())
            ->method('getEntities')
            ->willReturn(
                [
                    ['name' => 'TestEntity1', 'label' => 'entity1_label'],
                    ['name' => 'TestEntity2', 'label' => 'entity2_label'],
                ]
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
