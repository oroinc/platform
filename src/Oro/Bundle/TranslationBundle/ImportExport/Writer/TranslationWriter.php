<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Event\InvalidateDynamicTranslationCacheEvent;
use Oro\Bundle\TranslationBundle\EventListener\InvalidateDynamicJsTranslationListener;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Batch job's writer.
 */
class TranslationWriter implements ItemWriterInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TranslationManager */
    protected $translationManager;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    public function __construct(ManagerRegistry $registry, TranslationManager $translationManager)
    {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass(Translation::class);

        try {
            $em->beginTransaction();

            /** @var Translation $item */
            foreach ($items as $item) {
                $key = $item->getTranslationKey()->getKey();
                $domain = $item->getTranslationKey()->getDomain();
                $locale = $item->getLanguage()->getCode();

                $this->translationManager->saveTranslation(
                    $key,
                    $item->getValue(),
                    $locale,
                    $domain,
                    Translation::SCOPE_UI
                );
            }

            /**
             * Disable translation dumping.
             *
             * Since the cache is updated and translations are dumped for each batch of imports, this leads to
             * significant resource consumption.
             * Also, parallel recording to GridFS has its limitations on simultaneous recording,
             * so it is better to record one file after all translations have been updated.
             * (see @Oro\Bundle\TranslationBundle\EventListener\FinishImportListener::onFinishImport).
             *
             * Please note that disabling the listener must be global to avoid a situation where the cache is cleared
             * after one of the parties is finished.
             * (see @Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache)
             */
            $this->removeListener();
            $this->translationManager->flushWithoutDumpJsTranslations(true);

            $em->commit();
            $em->clear();
        } catch (\Exception $exception) {
            $em->rollback();
            if (!$em->isOpen()) {
                $this->registry->resetManager();
            }

            throw $exception;
        }
    }

    private function removeListener(): void
    {
        foreach ($this->eventDispatcher->getListeners(InvalidateDynamicTranslationCacheEvent::NAME) as $listener) {
            if ($listener[0] instanceof InvalidateDynamicJsTranslationListener) {
                $this->eventDispatcher->removeListener(InvalidateDynamicTranslationCacheEvent::NAME, $listener);
            }
        }
    }
}
