<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class StoreSqlExtension extends AbstractExtension
{
    const DISPLAY_SQL_QUERY  = 'display_sql_query';
    const STORED_SQL_PATH    = 'metadata[stored_sql]';
    const SQL                = 'sql';
    const STORE_SQL          = 'store_sql';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && $this->getParameters()->get(self::DISPLAY_SQL_QUERY, false)
            && $this->tokenAccessor->hasUser()
            && $this->authorizationChecker->isGranted('oro_report_view_sql');
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['ororeport/js/view-sql-query-builder']);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->offsetAddToArray(MetadataObject::OPTIONS_KEY, [self::STORE_SQL => true]);
    }

    /**
     * {@inheritdoc}
     *
     * Gets stored SQL and parameters prepared by
     * Oro\Bundle\DataGridBundle\EventListener\StoreSqlListener
     * and puts them into metadata of ResultsObject
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $value = $config->offsetGetByPath(self::STORED_SQL_PATH);
        if ($value) {
            $result->offsetAddToArray('metadata', [self::DISPLAY_SQL_QUERY => true]);
            $result->offsetAddToArrayByPath(
                self::STORED_SQL_PATH,
                $value
            );
        }
    }
}
