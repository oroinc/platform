<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Filter\EmailStringFilter;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Provides reusable utility methods to modify the query builder for email grids.
 */
class EmailQueryFactory
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var MailboxManager */
    protected $mailboxManager;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var FilterUtility */
    protected $filterUtil;

    /**
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param EntityNameResolver        $entityNameResolver
     * @param MailboxManager            $mailboxManager
     * @param TokenAccessorInterface    $tokenAccessor
     * @param FormFactoryInterface      $formFactory
     * @param FilterUtility             $filterUtil
     */
    public function __construct(
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EntityNameResolver $entityNameResolver,
        MailboxManager $mailboxManager,
        TokenAccessorInterface $tokenAccessor,
        FormFactoryInterface $formFactory,
        FilterUtility $filterUtil
    ) {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->entityNameResolver = $entityNameResolver;
        $this->mailboxManager = $mailboxManager;
        $this->tokenAccessor = $tokenAccessor;
        $this->formFactory = $formFactory;
        $this->filterUtil = $filterUtil;
    }

    /**
     * Adds "from" email address related columns to a query builder.
     * The following columns are added:
     * * fromEmailAddress - The text representation of email address
     * * fromEmailAddressOwnerClass - The class name of email address owner
     * * fromEmailAddressOwnerId - The id of email address owner
     *
     * @param QueryBuilder $qb                     The query builder to update
     * @param string       $emailAddressTableAlias The alias of the email address table
     */
    public function addFromEmailAddress(QueryBuilder $qb, $emailAddressTableAlias = 'a')
    {
        /**
         * Doctrine does not support NULL as a scalar expression
         * see https://github.com/doctrine/doctrine2/issues/5801
         * as result we have to use NULLIF(0, 0) and NULLIF('', '') instead of NULL
         */
        QueryBuilderUtil::checkIdentifier($emailAddressTableAlias);
        $providers = $this->emailOwnerProviderStorage->getProviders();
        if (empty($providers)) {
            $qb->addSelect('NULLIF(\'\', \'\') AS fromEmailAddressOwnerClass');
            $qb->addSelect('NULLIF(0, 0) AS fromEmailAddressOwnerId');
            $qb->addSelect(sprintf('%s.email AS fromEmailAddress', $emailAddressTableAlias));
        } else {
            $emailAddressExpression = '';
            $ownerClassExpression = '';
            $ownerIdExpression = '';
            foreach ($providers as $provider) {
                $ownerFieldName = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
                $ownerClass = $provider->getEmailOwnerClass();

                $ownerClassExpression .= sprintf(
                    'WHEN %s.%s IS NOT NULL THEN \'%s\' ',
                    $emailAddressTableAlias,
                    $ownerFieldName,
                    $ownerClass
                );
                if ($ownerIdExpression) {
                    $ownerIdExpression .= ', ';
                }
                $ownerIdExpression .= sprintf('IDENTITY(%s.%s) ', $emailAddressTableAlias, $ownerFieldName);
                $emailAddressExpression .= sprintf(
                    'WHEN %s.%s IS NOT NULL THEN %s ',
                    $emailAddressTableAlias,
                    $ownerFieldName,
                    $this->entityNameResolver->getNameDQL($ownerClass, $ownerFieldName)
                );

                $qb->leftJoin(sprintf('%s.%s', $emailAddressTableAlias, $ownerFieldName), $ownerFieldName);
            }

            $ownerClassExpression = sprintf(
                '(CASE %sELSE NULLIF(\'\', \'\') END) AS fromEmailAddressOwnerClass',
                $ownerClassExpression
            );
            $ownerIdExpression = sprintf(
                'COALESCE(%s) AS fromEmailAddressOwnerId',
                $ownerIdExpression
            );
            $emailAddressExpression = sprintf(
                'CONCAT(\'\', CASE WHEN %1$s.hasOwner = true THEN (%2$s) ELSE %1$s.email END) AS fromEmailAddress',
                $emailAddressTableAlias,
                sprintf('CASE %sELSE \'\' END', $emailAddressExpression)
            );

            $qb->addSelect($ownerClassExpression);
            $qb->addSelect($ownerIdExpression);
            $qb->addSelect($emailAddressExpression);
        }
    }

    /**
     * Apply custom ACL checks
     *
     * @param QueryBuilder $qb
     */
    public function applyAcl(QueryBuilder $qb)
    {
        $uoCheck = $qb->expr()->andX($qb->expr()->eq('eu.owner', ':owner'));

        $organization = $this->getOrganization();
        if ($organization) {
            $uoCheck->add($qb->expr()->eq('eu.organization ', ':organization'));
            $qb->setParameter('organization', $organization->getId());
        }

        $mailboxIds = $this->getAvailableMailboxIds();
        if (!empty($mailboxIds)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $uoCheck,
                    $qb->expr()->in('eu.mailboxOwner', ':mailboxIds')
                )
            );
            $qb->setParameter('mailboxIds', $mailboxIds);
        } else {
            $qb->andWhere($uoCheck);
        }

        $qb->setParameter('owner', $this->tokenAccessor->getUserId());
    }

    /**
     * Apply custom ACL checks in case of threaded emails view enabled.
     * Adds additional WHERE condition:
     *
     * o0_.id IN (
     *  SELECT max(u.id)
     *  FROM OroEmailBundle:EmailUser as u
     *  INNER JOIN OroEmailBundle:Email as m on u.email_id = m.id
     *  WHERE
     *    m.thread_id is not null
     *    AND (
     *      (
     *        u.user_owner_id = {owner_id}
     *        AND u.organization_id = {organization_id}
     *      )
     *      OR u.mailbox_owner_id IN ( {allowed_mailboxes_ids} )
     *    )
     *  GROUP BY m.thread_id
     * )
     * OR (o3_.is_head = 1 AND o3_.thread_id is null)
     *
     * @param QueryBuilder           $qb
     * @param DatagridInterface|null $datagrid
     * @param array                  $filters
     */
    public function applyAclThreadsGrouping(
        QueryBuilder $qb,
        $datagrid = null,
        $filters = []
    ) {
        $innerQb = $qb->getEntityManager()->createQueryBuilder();
        $innerQb
            ->select('MAX(u.id)')
            ->from('OroEmailBundle:EmailUser', 'u')
            ->innerJoin('OroEmailBundle:Email', 'm', 'WITH', 'u.email = m.id')
            ->where(
                $innerQb->expr()->andX(
                    $innerQb->expr()->isNotNull('m.thread'),
                    $this->getOwningExpression($innerQb->expr(), 'u')
                )
            )
            ->groupBy('m.thread');

        $expression = $qb->expr()->andX(
            $qb->expr()->isNull('e.thread'),
            $qb->expr()->eq('e.head', 'TRUE')
        );

        $threadedExpressions = null;
        $threadedExpressionsParameters = null;
        if ($datagrid && $filters) {
            list(
                $threadedExpressions,
                $threadedExpressionsParameters
            ) = $this->prepareSearchFilters($qb, $datagrid, $filters, 'mm');
        }

        if ($threadedExpressions) {
            $filterQb = $qb->getEntityManager()->createQueryBuilder();
            $filterExpressions = $filterQb->expr()->andX();
            $filterExpressions->addMultiple($threadedExpressions);
            $filterQb
                ->select('IDENTITY(mm.thread)')
                ->from('OroEmailBundle:EmailUser', 'uu')
                ->innerJoin('OroEmailBundle:Email', 'mm', 'WITH', 'uu.email = mm.id')
                ->where($filterExpressions);

            list(
                $notThreadedExpressions,
                $notThreadedExpressionsParameters
            ) = $this->prepareSearchFilters($qb, $datagrid, $filters, 'e');

            $expression->addMultiple($notThreadedExpressions);

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->in('eu.id', $innerQb->getDQL()),
                        $qb->expr()->in('e.thread', $filterQb->getDQL())
                    ),
                    $expression
                )
            );

            /** @var Parameter[] $params */
            $params = array_merge($threadedExpressionsParameters, $notThreadedExpressionsParameters);
            foreach ($params as $param) {
                $qb->setParameter($param->getName(), $param->getValue(), $param->getType());
            }
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('eu.id', $innerQb->getDQL()),
                    $expression
                )
            );
        }
    }

    /**
     * In case of threaded emails view enabled, adds email counting (SELECT COUNT) expression.
     *
     * SELECT COUNT(eu.id)
     *   FROM oro_email_user AS eu
     *   INNER JOIN oro_email e ON (eu.email_id = e.id)
     *   WHERE
     *     e.thread_id = o3_.thread_id
     *     AND (
     *       (
     *         eu.user_owner_id = {owner_id}
     *         AND eu.organization_id = {organization_id}
     *       )
     *       OR eu.mailbox_owner_id IN ( {allowed_mailboxes_ids} )
     *    )
     *    AND o3_.thread_id IS NOT NULL -- `o3_` is alias for `oro_email` table from base query
     * as thread_email_count
     *
     * @param QueryBuilder $qb
     * @param bool         $isThreadGroupingEnabled
     */
    public function addEmailsCount(QueryBuilder $qb, $isThreadGroupingEnabled)
    {
        // in case threading view is disabled the default value for counting is `0`
        $selectExpression = '0 AS thread_email_count';

        if ($isThreadGroupingEnabled) {
            $innerQb = $qb->getEntityManager()->createQueryBuilder();
            $innerQb
                ->select('COUNT(emailUser.id)')
                ->from('OroEmailBundle:EmailUser', 'emailUser')
                ->innerJoin('OroEmailBundle:Email', 'email', 'WITH', 'emailUser.email = email.id')
                ->where(
                    $innerQb->expr()->andX(
                        $innerQb->expr()->isNotNull('e.thread'),
                        $innerQb->expr()->eq('email.thread', 'e.thread'),
                        $this->getOwningExpression($innerQb->expr(), 'emailUser')
                    )
                );

            $selectExpression = '(' . $innerQb->getDQL() . ') AS thread_email_count';
        }

        $qb->addSelect($selectExpression);
    }

    /**
     * @param QueryBuilder      $qb
     * @param DatagridInterface $datagrid
     * @param array             $filters
     * @param string            $alias
     *
     * @return array
     */
    protected function prepareSearchFilters($qb, $datagrid, $filters, $alias = '')
    {
        $searchExpressions = null;
        $searchExpressionsParameters = null;
        $filterColumnsMap = [
            'subject' => 'subject',
            'from'    => 'fromName',
        ];
        $searchFilters = array_intersect_key(
            $filters,
            array_flip(array_keys($filterColumnsMap))
        );

        if ($searchFilters) {
            $queryBuilder = clone $qb;

            $datagridConfig = $datagrid->getConfig();
            $filterTypes = $datagridConfig->offsetGetByPath('[filters][columns]');

            foreach ($searchFilters as $columnName => $filterData) {
                $filterConfig = $filterTypes[$columnName];
                $filterConfig['data_name'] = $alias
                    ? QueryBuilderUtil::getField($alias, $filterColumnsMap[$columnName])
                    : $filterConfig['data_name'];

                $datasourceAdapter = new OrmFilterDatasourceAdapter($queryBuilder);
                $mFilter = new EmailStringFilter($this->formFactory, $this->filterUtil);
                $mFilter->init($columnName, $filterConfig);
                $expressionAndParameters = $mFilter->applyAndGetExpression($datasourceAdapter, $filterData);
                if ($expressionAndParameters !== null) {
                    [$searchExpressions, $searchExpressionsParameters] = $expressionAndParameters;
                }
            }
        }

        if (null !== $searchExpressions && !is_array($searchExpressions)) {
            $searchExpressions = [$searchExpressions];
        }

        return [
            $searchExpressions,
            $searchExpressionsParameters
        ];
    }

    /**
     * Builds owning expression part, being used in case of threaded emails view enabled.
     *
     *  (
     *    eu.user_owner_id = {owner_id}
     *    AND eu.organization_id = {organization_id}
     *  )
     *  OR eu.mailbox_owner_id IN ( {allowed_mailboxes_ids} )
     *
     * @param Expr   $expr
     * @param string $tableAlias
     *
     * @return Expr\Andx|Expr\Comparison|Expr\Orx
     */
    protected function getOwningExpression($expr, $tableAlias)
    {
        $user = $this->tokenAccessor->getUser();
        $organization = $this->getOrganization();

        if ($organization === null) {
            $ownerExpression =
                $expr->eq($tableAlias . '.owner', $user->getId());
        } else {
            $ownerExpression = $expr->andX(
                $expr->eq($tableAlias . '.owner', $user->getId()),
                $expr->eq($tableAlias . '.organization', $organization->getId())
            );
        }

        $availableMailboxIds = $this->getAvailableMailboxIds();
        if ($availableMailboxIds) {
            return $expr->orX(
                $ownerExpression,
                $expr->in($tableAlias . '.mailboxOwner', $this->getAvailableMailboxIds())
            );
        } else {
            return $ownerExpression;
        }
    }

    /**
     * @return array
     */
    protected function getAvailableMailboxIds()
    {
        return $this->mailboxManager->findAvailableMailboxIds(
            $this->tokenAccessor->getUser(),
            $this->getOrganization()
        );
    }

    /**
     * @return Organization|null
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }
}
