<?php

namespace Oro\Bundle\ReportBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Extends datagrid functionality to capture and display executed SQL queries for authorized users.
 *
 * This extension enables SQL query visibility in datagrids when the `display_sql_query` parameter
 * is enabled and the current user has the `oro_report_view_sql` permission. It works in conjunction
 * with {@see StoreSqlListener} to capture the executed SQL and its parameters, then makes this information
 * available in the datagrid metadata. This is particularly useful for debugging, auditing, and
 * understanding the actual queries being executed by the report system. The extension only applies
 * to ORM-based datagrids and requires an authenticated user.
 */
class StoreSqlExtension extends AbstractExtension
{
    public const DISPLAY_SQL_QUERY  = 'display_sql_query';
    public const STORED_SQL_PATH    = 'metadata[stored_sql]';
    public const SQL                = 'sql';
    public const STORE_SQL          = 'store_sql';

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && $this->getParameters()->get(self::DISPLAY_SQL_QUERY, false)
            && $this->tokenAccessor->hasUser()
            && $this->authorizationChecker->isGranted('oro_report_view_sql');
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $data->offsetAddToArray(MetadataObject::REQUIRED_MODULES_KEY, ['ororeport/js/view-sql-query-builder']);
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config)
    {
        $config->offsetAddToArray(MetadataObject::OPTIONS_KEY, [self::STORE_SQL => true]);
    }

    #[\Override]
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
