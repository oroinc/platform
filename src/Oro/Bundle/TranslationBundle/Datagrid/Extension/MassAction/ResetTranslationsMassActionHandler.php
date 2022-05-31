<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The datagrid mass action for reset translated values.
 */
class ResetTranslationsMassActionHandler implements MassActionHandlerInterface
{
    private const FLUSH_BATCH_SIZE = 100;

    private TranslationManager $translationManager;
    private TranslatorInterface $translator;
    private AclHelper $aclHelper;

    public function __construct(
        TranslationManager $translationManager,
        TranslatorInterface $translator,
        AclHelper $aclHelper
    ) {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $datasource = $args->getDatagrid()->getDatasource();
        if (!$datasource instanceof OrmDatasource) {
            throw new \InvalidArgumentException(sprintf(
                'Expected "%s", "%s" given',
                OrmDatasource::class,
                get_class($datasource)
            ));
        }

        $qb = clone $datasource->getQueryBuilder();
        $qb->addSelect('translation.scope as translation_scope');
        $this->removeEmptyValues($qb);
        $this->aclHelper->apply($qb, 'TRANSLATE');
        $results = $qb->getQuery()->iterate(null, Query::HYDRATE_SCALAR);

        // if huge amount data must be deleted
        set_time_limit(0);

        $iteration = 0;
        foreach ($results as $result) {
            $translationData = reset($result);
            if ($translationData === false) {
                continue;
            }

            if (null !== $translationData['id']) {
                $this->translationManager->saveTranslation(
                    $translationData['key'],
                    null,
                    $translationData['code'],
                    $translationData['domain'],
                    $translationData['translation_scope']
                );
            }
            $iteration++;

            if ($iteration % self::FLUSH_BATCH_SIZE === 0) {
                $this->translationManager->flush();
            }
        }

        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->translationManager->flush();
        }

        return new MassActionResponse(
            $iteration > 0,
            $this->translator->trans('oro.translation.action.reset.success'),
            ['count' => $iteration]
        );
    }

    private function removeEmptyValues(QueryBuilder $qb): void
    {
        $valuesParameter = $qb->getParameter('values');
        if (null === $valuesParameter) {
            return;
        }

        $valuesParameter->setValue(array_filter($valuesParameter->getValue()));
    }
}
