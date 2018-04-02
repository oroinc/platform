<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\Form\FormFactoryInterface;

class ChoiceMessageTypeFilter extends ChoiceFilter
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /**
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     */
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
        $bothValuesSelected = in_array(FolderType::INBOX, $data['value'], true) &&
                              in_array(FolderType::SENT, $data['value'], true);

        $noValueSelected = !in_array(FolderType::INBOX, $data['value'], true) &&
                           !in_array(FolderType::SENT, $data['value'], true);

        if ($bothValuesSelected) {
            $data['value'] = [];
            return parent::apply($ds, $data);
        } elseif ($noValueSelected) {
            return parent::apply($ds, $data);
        }

        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        if (in_array(FolderType::INBOX, $data['value'], true)) {
            $this->applyInboxFilter($ds);
        } else {
            $this->applySentFilter($ds);
        }

        return true;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     */
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

        list($dql, $replacements) = $this->createDQLWithReplacedAliases($ds, $subQb);

        $replacedFieldExpr = QueryBuilderUtil::getField($replacements['eu'], 'id');
        $oldExpr = sprintf('%1$s = %1$s', $replacedFieldExpr);
        $newExpr = sprintf('%s = eu.id', $replacedFieldExpr);
        $dql = strtr($dql, [$oldExpr => $newExpr]);
        $qb
            ->setParameter('outgoing_types', FolderType::outgoingTypes())
            ->setParameter('incoming_types', FolderType::incomingTypes())
            ->andWhere($qb->expr()->exists($dql));
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     */
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
        list($dql, $replacements) = $this->createDQLWithReplacedAliases($ds, $subQb);

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
                    return $provider->getEmailOwnerClass() === 'Oro\Bundle\UserBundle\Entity\User';
                },
                $this->emailOwnerProviderStorage->getProviders()
            )
        );
    }
}
