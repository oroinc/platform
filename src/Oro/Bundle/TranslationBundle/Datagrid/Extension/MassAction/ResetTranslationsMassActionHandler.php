<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ResetTranslationsMassActionHandler implements MassActionHandlerInterface
{
    const FLUSH_BATCH_SIZE = 100;
    const MAX_DELETE_RECORDS = 5000;

    /**
     * @var TranslationManager
     */
    private $translationManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @param TranslationManager $translationManager
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        TranslationManager $translationManager,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade
    ) {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(MassActionHandlerArgs $args)
    {
        $iteration = 0;
        $entityName = Translation::class;

        /** @var QueryBuilder $qb */
        $qb = $args->getDatagrid()->getDatasource()->getQueryBuilder();
        $qb->setMaxResults(self::MAX_DELETE_RECORDS);

        $this->removeEmptyValuesFromQB($qb);

        $results = new IterableResult($qb);
        $results->setBufferSize(self::FLUSH_BATCH_SIZE);

        // if huge amount data must be deleted
        set_time_limit(0);

        /** @var EntityManager $manager */
        $manager = $this->doctrineHelper->getEntityManagerForClass($entityName);
        foreach ($results as $result) {
            /** @var $result ResultRecordInterface */
            $entity = $result->getRootEntity();
            $identifierValue = $result->getValue('id');

            if (!$identifierValue) {
                continue;
            }

            if (!$entity) {
                // no entity in result record, it should be extracted from DB
                $entity = $manager->getReference($entityName, $identifierValue);
            }

            if (!$entity) {
                continue;
            }

            if (!$this->securityFacade->isGranted('TRANSLATE', $entity)) {
                continue;
            }
            $this->processReset($entity);
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
     * @param Translation $translation
     */
    private function processReset(Translation $translation)
    {
        $translationKey = $translation->getTranslationKey();
        $locale = $translation->getLanguage()->getCode();
        $domain = $translationKey->getDomain();
        $scope = $translation->getScope();

        $this->translationManager->saveTranslation($translationKey->getKey(), null, $locale, $domain, $scope);
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
}
