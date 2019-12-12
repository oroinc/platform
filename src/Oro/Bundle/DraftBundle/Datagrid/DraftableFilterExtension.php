<?php

namespace Oro\Bundle\DraftBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterManager;

/**
 * Disable Draftable Filter based on the showDrafts option
 */
class DraftableFilterExtension extends AbstractExtension
{
    public const SHOW_DRAFTS_CONFIG_PATH = '[options][showDrafts]';

    /** @var DraftableFilterManager */
    private $filterManager;

    /**
     * @param DraftableFilterManager $filterManager
     */
    public function __construct(DraftableFilterManager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return
            parent::isApplicable($config)
            && $config->offsetGetByPath(self::SHOW_DRAFTS_CONFIG_PATH, false) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $from = $config->getOrmQuery()->getFrom();
        $fromPart = reset($from);

        if (isset($fromPart['table'])) {
            $this->filterManager->disable($fromPart['table']);
        }
    }
}
