<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\Toolbar\ToolbarExtension;

class DataGridTagListener
{
    const TAGS_PATH = '[contentTags]';

    /** @var TagGeneratorChain */
    protected $generator;

    public function __construct(TagGeneratorChain $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Check whenever datasource is ORM and try to generate tags
     * It will be available in metadata and after build will be passed to content-manager
     *
     * @param BuildAfter $event
     */
    public function buildAfter(BuildAfter $event)
    {
        $grid       = $event->getDatagrid();
        $datasource = $grid->getDatasource();
        $config     = $grid->getAcceptor()->getConfig();

        if ($datasource instanceof OrmDatasource) {
            // autogenerate only in case when it's not passed directly in config
            if (!$config->offsetGetByPath(ToolbarExtension::OPTIONS_PATH . self::TAGS_PATH)) {
                $tags = [];
                $qb   = $datasource->getQueryBuilder();

                $fromParts = $qb->getDQLPart('from');
                /** @var \Doctrine\ORM\Query\Expr\From $singleTableMetadata */
                foreach ($fromParts as $singleTableMetadata) {
                    $tags = array_merge($tags, $this->generator->generate($singleTableMetadata->getFrom(), true));
                }

                $config->offsetSetByPath(ToolbarExtension::OPTIONS_PATH . self::TAGS_PATH, $tags);
            }

            $options = $config->offsetGetByPath(ToolbarExtension::OPTIONS_PATH, []);
            $modules = !empty($options[MetadataObject::REQUIRED_MODULES_KEY])
                ? $options[MetadataObject::REQUIRED_MODULES_KEY] : [];
            $config->offsetSetByPath(
                sprintf('%s[%s]', ToolbarExtension::OPTIONS_PATH, MetadataObject::REQUIRED_MODULES_KEY),
                array_merge($modules, ['oronavigation/js/content/grid-builder'])
            );
        }
    }
}
