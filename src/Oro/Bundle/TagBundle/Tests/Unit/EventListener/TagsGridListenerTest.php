<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TagBundle\EventListener\TagsGridListener;

class TagsGridListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnBuildBefore()
    {
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable');

        $listener = new TagsGridListener($entityClassResolver);

        $config   = DatagridConfiguration::create(
            [
                'name'                 => 'test_grid',
                'extended_entity_name' => 'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                'source'               => [
                    'query' => [
                        'select' => ['t.id'],
                        'from'   => [
                            [
                                'table' => 'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                                'alias' => 't'
                            ]
                        ]
                    ]
                ],
                'columns'              => ['id' => ['label' => 'id']],
                'filters'              => [
                    'columns' => [
                        'id' => ['type' => 'string']
                    ]
                ]
            ]
        );
        $datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()
            ->getMock();
        $event    = new BuildBefore($datagrid, $config);
        $listener->onBuildBefore($event);
        $this->assertEquals('COUNT(tag.id) as tagsCount', $config->offsetGetByPath('[source][query][select][1]'));
        $this->assertEquals([
            'type'         => 'entity',
            'label'        => 'oro.tag.entity_plural_label',
            'data_name'    => 'tag.id',
            'enabled'      => false,
            'translatable' => true,
            'options'      => [
                'field_type'    => 'oro_tag_entity_tags_selector',
                'field_options' => [
                    'entity_class'         => 'Oro\Bundle\TagBundle\Tests\Unit\Fixtures\Taggable',
                    'multiple'             => true,
                    'translatable_options' => true
                ]
            ]
        ], $config->offsetGetByPath('[filters][columns][tagname]'));
        $this->assertEquals([
            'left' => [
                [
                    'join'          => 'Oro\Bundle\TagBundle\Entity\Tagging',
                    'alias'         => 'tagging',
                    'conditionType' => 'WITH',
                    'condition'     => "(tagging.entityName = "
                        . "'Oro\\Bundle\\TagBundle\\Tests\\Unit\\Fixtures\\Taggable' and tagging.recordId = t.id)",
                ],
                ['join' => 'tagging.tag', 'alias' => 'tag']
            ]
        ], $config->offsetGetByPath('[source][query][join]'));
    }
}
