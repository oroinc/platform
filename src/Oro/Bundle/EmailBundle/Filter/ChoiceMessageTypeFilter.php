<?php

namespace Oro\Bundle\EmailBundle\Filter;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;

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

        if (in_array(FolderType::INBOX, $data['value']) && in_array(FolderType::SENT, $data['value'])) {
            $data['value'] = [];
            return parent::apply($ds, $data);
        } elseif (!in_array(FolderType::INBOX, $data['value']) && !in_array(FolderType::SENT, $data['value'])) {
            return parent::apply($ds, $data);
        }

        if (!$ds instanceof OrmFilterDatasourceAdapter) {
            return false;
        }

        $qb = $ds->getQueryBuilder();
        if (in_array(FolderType::INBOX, $data['value'])) {
            $this->applyInboxFilter($qb);
        } else {
            $this->applySentFilter($qb);
        }

        return true;
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function applyInboxFilter(QueryBuilder $qb)
    {
        $qb
            ->leftJoin('e.fromEmailAddress', '_fea')
            ->leftJoin(sprintf('_fea.%s', $this->getUserOwnerFieldName()), '_fo')
            ->leftJoin('eu.owner', '_eo')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('f.type', ':incoming_types'),
                    $qb->expr()->andX(
                        $qb->expr()->notIn('f.type', ':outcoming_types'),
                        $qb->expr()->orX(
                            $qb->expr()->andX(
                                $qb->expr()->isNull('_eo.id'),
                                $qb->expr()->isNotNull('_fo.id')
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('_eo.id'),
                                $qb->expr()->isNull('_fo.id')
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->isNotNull('_eo.id'),
                                $qb->expr()->isNotNull('_fo.id'),
                                $qb->expr()->neq('_fo.id', '_eo.id')
                            )
                        )
                    )
                )
            )
            ->setParameter('outcoming_types', FolderType::outcomingTypes())
            ->setParameter('incoming_types', FolderType::incomingTypes());
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function applySentFilter(QueryBuilder $qb)
    {
        $qb
            ->leftJoin('e.fromEmailAddress', '_fea')
            ->leftJoin(sprintf('_fea.%s', $this->getUserOwnerFieldName()), '_fo')
            ->leftJoin('eu.owner', '_eo')
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('f.type', ':outcoming_types'),
                    $qb->expr()->andX(
                        $qb->expr()->notIn('f.type', ':incoming_types'),
                        $qb->expr()->isNotNull('_eo.id'),
                        $qb->expr()->eq('_fo.id', '_eo.id')
                    )
                )
            )
            ->setParameter('outcoming_types', FolderType::outcomingTypes())
            ->setParameter('incoming_types', FolderType::incomingTypes());
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
