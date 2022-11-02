<?php

namespace Oro\Bundle\DraftBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DraftBundle\Acl\AccessRule\DraftAccessRule;
use Oro\Bundle\DraftBundle\Manager\DraftableFilterManager;

/**
 * Disable Draftable Filter and enable DraftAccessRule based on the showDrafts option
 */
class DraftableFilterExtension extends AbstractExtension
{
    public const SHOW_DRAFTS_CONFIG_PATH = '[options][showDrafts]';

    /** @var DraftableFilterManager */
    private $filterManager;

    /** @var DraftAccessRule */
    private $draftAccessRule;

    /** @var string|null */
    private $className;

    public function __construct(
        DraftableFilterManager $filterManager,
        DraftAccessRule $draftAccessRule
    ) {
        $this->filterManager = $filterManager;
        $this->draftAccessRule = $draftAccessRule;
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
            $this->className = $fromPart['table'];
            $this->filterManager->disable($this->className);
        }

        $this->draftAccessRule->setEnabled(true);
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        if ($this->className) {
            $this->filterManager->enable($this->className);
            $this->className = null;
        }
        $this->draftAccessRule->setEnabled(false);
    }
}
