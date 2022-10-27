<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by incoming and outgoing email messages.
 */
class ChoiceMessageTypeFilter extends ChoiceFilter
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        EmailOwnerProviderStorage $emailOwnerProviderStorage
    ) {
        parent::__construct($factory, $util);
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $inboxSelected = in_array(FolderType::INBOX, $data['value'], true);
        $sentSelected = in_array(FolderType::SENT, $data['value'], true);

        if ($inboxSelected && $sentSelected) {
            $data['value'] = [];

            return parent::apply($ds, $data);
        }

        if (!$inboxSelected && !$sentSelected) {
            return parent::apply($ds, $data);
        }

        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        if ($inboxSelected) {
            $this->applyInboxFilter($ds);
        } else {
            $this->applySentFilter($ds);
        }

        return true;
    }

    protected function applyInboxFilter(OrmFilterDatasourceAdapter $ds)
    {
        $qb = $ds->getQueryBuilder();
        $subQb = clone $qb;
        $subQb
            ->resetDQLPart('where')
            ->resetDQLPart('orderBy')
            ->select('eu.id')
            ->leftJoin('eu.folders', '_cmtf_folders')
            ->leftJoin('e.fromEmailAddress', '_cmtf_fea')
            ->leftJoin(sprintf('_cmtf_fea.%s', $this->getUserOwnerFieldName()), '_cmtf_fo')
            ->leftJoin('eu.owner', '_cmtf_eo')
            ->andWhere('eu.id = eu.id')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('f.type', ':incoming_types'),
                    $qb->expr()->andX(
                        $qb->expr()->notIn('f.type', ':outgoing_types'),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->isNull('_cmtf_eo.id'),
                                $qb->expr()->isNotNull('_cmtf_fo.id')
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('_cmtf_eo.id'),
                                $qb->expr()->isNull('_cmtf_fo.id')
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('_cmtf_eo.id'),
                                $qb->expr()->isNotNull('_cmtf_fo.id'),
                                $qb->expr()->neq('_cmtf_fo.id', '_cmtf_eo.id')
                            )
                        )
                    )
                )
            );

        [$dql, $replacements] = $this->createDqlWithReplacedAliases($ds, $subQb);

        $replacedFieldExpr = QueryBuilderUtil::getField($replacements['eu'], 'id');
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = eu.id', $replacedFieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);
        $qb
            ->setParameter('outgoing_types', FolderType::outgoingTypes())
            ->setParameter('incoming_types', FolderType::incomingTypes())
            ->andWhere($qb->expr()->exists($dql));
    }

    protected function applySentFilter(OrmFilterDatasourceAdapter $ds)
    {
        $qb = $ds->getQueryBuilder();
        $subQb = clone $qb;
        $subQb
            ->resetDQLPart('where')
            ->resetDQLPart('orderBy')
            ->select('eu.id')
            ->leftJoin('eu.folders', '_cmtf_folders')
            ->leftJoin('e.fromEmailAddress', '_cmtf_fea')
            ->leftJoin(sprintf('_cmtf_fea.%s', $this->getUserOwnerFieldName()), '_cmtf_fo')
            ->leftJoin('eu.owner', '_cmtf_eo')
            ->andWhere('eu.id = eu.id')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('_cmtf_folders.type', ':outgoing_types'),
                    $qb->expr()->andX(
                        $qb->expr()->notIn('_cmtf_folders.type', ':incoming_types'),
                        $qb->expr()->isNotNull('_cmtf_eo.id'),
                        $qb->expr()->eq('_cmtf_fo.id', '_cmtf_eo.id')
                    )
                )
            );
        [$dql, $replacements] = $this->createDqlWithReplacedAliases($ds, $subQb);

        $replacedFieldExpr = sprintf('%s.%s', $replacements['eu'], 'id');
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = eu.id', $replacedFieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);
        $qb
            ->setParameter('outgoing_types', FolderType::outgoingTypes())
            ->setParameter('incoming_types', FolderType::incomingTypes())
            ->andWhere($qb->expr()->exists($dql));
    }

    /**
     * @return string
     */
    protected function getUserOwnerFieldName()
    {
        return $this->emailOwnerProviderStorage->getEmailOwnerFieldName(
            ArrayUtil::find(
                function (EmailOwnerProviderInterface $provider) {
                    return $provider->getEmailOwnerClass() === User::class;
                },
                $this->emailOwnerProviderStorage->getProviders()
            )
        );
    }
}
