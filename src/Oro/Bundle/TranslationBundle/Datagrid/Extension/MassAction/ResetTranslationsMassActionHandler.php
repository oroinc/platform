<?php

namespace Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerInterface;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The datagrid mass action for reset translated values.
 */
class ResetTranslationsMassActionHandler implements MassActionHandlerInterface
{
    private const FLUSH_BATCH_SIZE = 500;

    private TranslationManager $translationManager;
    private TranslatorInterface $translator;
    private AclHelper $aclHelper;
    private MessageProducerInterface $producer;

    public function __construct(
        TranslationManager $translationManager,
        TranslatorInterface $translator,
        AclHelper $aclHelper,
        MessageProducerInterface $producer
    ) {
        $this->translationManager = $translationManager;
        $this->translator = $translator;
        $this->aclHelper = $aclHelper;
        $this->producer = $producer;
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
                \get_class($datasource)
            ));
        }

        $qb = clone $datasource->getQueryBuilder();
        $qb->addSelect('translation.scope as translation_scope');
        $valuesParameter = $qb->getParameter('values');
        if (null !== $valuesParameter) {
            $valuesParameter->setValue(array_filter($valuesParameter->getValue()));
        }
        $this->aclHelper->apply($qb, 'TRANSLATE');
        $translations = $qb->getQuery()->iterate(null, Query::HYDRATE_SCALAR);

        // if huge amount data must be deleted
        set_time_limit(0);

        $totalCount = $this->resetTranslations($translations, $qb->getEntityManager());

        return new MassActionResponse(
            $totalCount > 0,
            $this->translator->trans('oro.translation.action.reset.success'),
            ['count' => $totalCount]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function resetTranslations(\Traversable $translations, EntityManagerInterface $em): int
    {
        $hasJsTranslations = false;
        $totalCount = 0;
        $updateCount = 0;
        foreach ($translations as $item) {
            $translationData = reset($item);
            if (false === $translationData) {
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
                $updateCount++;
            }
            $totalCount++;

            if ($updateCount > 0 && ($updateCount % self::FLUSH_BATCH_SIZE) === 0) {
                if ($this->flushTranslations($em)) {
                    $hasJsTranslations = true;
                }
                $updateCount = 0;
            }
        }
        if ($updateCount > 0 && $this->flushTranslations($em)) {
            $hasJsTranslations = true;
        }

        if ($hasJsTranslations) {
            $this->producer->send(DumpJsTranslationsTopic::getName(), []);
        }

        return $totalCount;
    }

    private function flushTranslations(EntityManagerInterface $em): bool
    {
        $hasJsTranslations = $this->translationManager->flushWithoutDumpJsTranslations();

        $em->clear(Translation::class);
        $em->clear(TranslationKey::class);

        return $hasJsTranslations;
    }
}
