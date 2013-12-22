<?php

namespace Oro\Bundle\NavigationBundle\Content;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class DataGridTagListener
{
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

        if ($datasource instanceof OrmDatasource) {
            $tags = [];
            $qb   = $datasource->getQueryBuilder();

            $fromParts = $qb->getDQLPart('from');
            /** @var \Doctrine\ORM\Query\Expr\From $singleTableMetadata */
            foreach ($fromParts as $singleTableMetadata) {
                $tags = array_merge($tags, $this->generator->generate($singleTableMetadata->getFrom(), true));
            }

            $grid->getAcceptor()->getConfig()->offsetSetByPath('[options][contentTags]', $tags);
        }
    }
}
