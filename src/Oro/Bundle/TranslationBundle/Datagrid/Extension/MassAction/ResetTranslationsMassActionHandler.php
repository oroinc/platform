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
use Symfony\Component\Translation\TranslatorInterface;

class ResetTranslationsMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;

    /**
     * @var TranslationManager
     */
    private $translationManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param TranslationManager $translationManager
     * @param TranslatorInterface $translator
     * @param AclHelper $aclHelper
     */
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
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected "%s", "%s" given',
                    OrmDatasource::class,
                    get_class($datasource)
                )
            );
        }

        $qb = clone $datasource->getQueryBuilder();

        $this->removeEmptyValuesFromQB($qb);
        $this->addRequiredParameters($qb);

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

            $this->processReset($translationData);
            $iteration++;

            if ($iteration % self::FLUSH_BATCH_SIZE === 0) {
                $this->finishBatch();
            }
        }

        if ($iteration % self::FLUSH_BATCH_SIZE > 0) {
            $this->finishBatch();
        }

        return $this->getResetResponse($iteration);
    }

    /**
     * Finish processed batch
     */
    private function finishBatch()
    {
        $this->translationManager->flush();
    }

    /**
     * @param int $entitiesCount
     * @return MassActionResponse
     */
    private function getResetResponse($entitiesCount)
    {
        $successful = $entitiesCount > 0;
        $options = ['count' => $entitiesCount];

        return new MassActionResponse(
            $successful,
            $this->translator->trans('oro.translation.action.reset.success'),
            $options
        );
    }

    /**
     * @param array $translationData
     */
    private function processReset(array $translationData)
    {
        if ($translationData['id'] === null) {
            return;
        }

        $this->translationManager->saveTranslation(
            $translationData['key'],
            null,
            $translationData['code'],
            $translationData['domain'],
            $translationData['translation_scope']
        );
    }

    /**
     * @param QueryBuilder $qb
     */
    private function removeEmptyValuesFromQB(QueryBuilder $qb)
    {
        $valuesParameter = $qb->getParameter('values');

        if (!$valuesParameter) {
            return;
        }

        $values = $valuesParameter->getValue();
        $values = array_filter($values);

        $valuesParameter->setValue($values);
    }

    /**
     * @param QueryBuilder $qb
     */
    private function addRequiredParameters(QueryBuilder $qb)
    {
        $qb->addSelect('translation.scope as translation_scope');
    }
}
