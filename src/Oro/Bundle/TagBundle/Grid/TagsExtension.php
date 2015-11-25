<?php

namespace Oro\Bundle\TagBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\TagBundle\Entity\TagManager;

class TagsExtension extends AbstractExtension
{
    const COLUMN_NAME = 'tags';

    const GRID_EXTEND_ENTITY_PATH = '[extended_entity_name]';
    const GRID_FROM_PATH          = '[source][query][from]';
    const GRID_FILTERS_PATH       = '[filters][columns]';
    const GRID_NAME_PATH          = 'name';
    const FILTER_COLUMN_NAME      = 'tagname';
    const PROPERTY_ID_PATH        = '[properties][id]';

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var TagManager */
    protected $tagManager;

    /**
     * @param TagManager          $tagManager
     * @param EntityClassResolver $resolver
     */
    public function __construct(TagManager $tagManager, EntityClassResolver $resolver)
    {
        $this->tagManager          = $tagManager;
        $this->entityClassResolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            null !== $config->offsetGetByPath(self::PROPERTY_ID_PATH)
            && $this->tagManager->isTaggable($this->getEntityClassName($config));
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath('[columns]') ? : [];
        $config->offsetSetByPath(
            '[columns]',
            array_merge(
                $columns,
                [
                    self::COLUMN_NAME => [
                        'label'         => 'oro.tag.tags_label',
                        'type'          => 'twig',
                        'frontend_type' => 'html',
                        'template'      => 'OroTagBundle:Datagrid:Property/tags.html.twig',
                        'editable'      => false,
                        'translatable'  => true,
                        'renderable'    => false
                    ]
                ]
            )
        );

        $filters = $config->offsetGetByPath(self::GRID_FILTERS_PATH, []);
        //@TODO Need recheck this condition
        if (empty($filters) || strpos($config->offsetGetByPath(self::GRID_NAME_PATH), 'oro_report') === 0) {
            return;
        }

        $filters[self::FILTER_COLUMN_NAME] = [
            'type'      => 'tag',
            'label'     => 'oro.tag.entity_plural_label',
            'data_name' => 'tag.id',
            'enabled'   => false,
            'options'   => [
                'field_options' => [
                    'entity_class' => $this->getEntityClassName($config),
                ]
            ]
        ];
        $config->offsetSetByPath(self::GRID_FILTERS_PATH, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $rows = (array)$result->offsetGetOr('data', []);
        $ids  = array_map(
            function (ResultRecord $item) {
                return $item->getValue('id');
            },
            $rows
        );
        //@TODO Should we apply acl?
        $tags = array_reduce(
            $this->tagManager->getTagsByEntityIds($this->getEntityClassName($config), $ids),
            function ($entitiesTags, $item) {
                $entitiesTags[$item['entityId']][] = $item;

                return $entitiesTags;
            },
            []
        );

        $result->offsetSet(
            'data',
            array_map(
                function (ResultRecord $item) use ($tags) {
                    $id = $item->getValue('id');
                    if (isset($tags[$id])) {
                        $item->addData(['tags' => $tags[$id]]);
                    }

                    return $item;
                },
                $rows
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    protected function getEntityClassName(DatagridConfiguration $config)
    {
        $entityClassName = $config->offsetGetByPath(self::GRID_EXTEND_ENTITY_PATH);
        if (!$entityClassName) {
            $from = $config->offsetGetByPath(self::GRID_FROM_PATH);
            if (!$from) {
                return null;
            }

            $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
        }

        return $entityClassName;
    }
}
