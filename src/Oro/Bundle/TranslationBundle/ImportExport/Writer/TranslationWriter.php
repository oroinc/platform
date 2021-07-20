<?php

namespace Oro\Bundle\TranslationBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

/**
 * Batch job's writer.
 */
class TranslationWriter implements ItemWriterInterface
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var TranslationManager */
    protected $translationManager;

    public function __construct(ManagerRegistry $registry, TranslationManager $translationManager)
    {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
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

            $this->translationManager->flush(true);

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
}
