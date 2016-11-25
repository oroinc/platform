<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class DatabasePersister
{
    /** @var int */
    private $batchSize = 200;

    /** @var Registry */
    private $registry;

    /** @var TranslationManager */
    private $translationManager;

    /** @var array */
    private $toWrite = [];

    /**
     * @param Registry $registry
     * @param TranslationManager $translationManager
     */
    public function __construct(Registry $registry, TranslationManager $translationManager)
    {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
    }

    /**
     * Persists data into DB in single transaction
     *
     * @param string $locale
     * @param array  $data translations strings, format same as MassageCatalog::all() returns
     *
     * @throws \Exception
     */
    public function persist($locale, array $data)
    {
        $writeCount = 0;
        $em = $this->getEntityManager();

        try {
            $em->beginTransaction();
            foreach ($data as $domain => $domainData) {
                foreach ($domainData as $key => $translation) {
                    if (strlen($key) > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                        continue;
                    }

                    $writeCount++;
                    $this->toWrite[] = ['key' => $key, 'translation' => $translation, 'domain' => $domain];
                    if (0 === $writeCount % $this->batchSize) {
                        $this->write($locale, $this->toWrite);

                        $this->toWrite = [];
                    }
                }
            }

            if (count($this->toWrite) > 0) {
                $this->write($locale, $this->toWrite);

                $this->toWrite = [];
            }

            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();

            throw $exception;
        }

        // update timestamp in case when persist succeed
        $this->translationManager->invalidateCache($locale);
    }

    /**
     * Flush all changes
     *
     * @param string $locale
     * @param array $items
     */
    private function write($locale, array $items)
    {
        foreach ($items as $item) {
            $this->translationManager->saveTranslation(
                $item['key'],
                $item['translation'],
                $locale,
                $item['domain'],
                Translation::SCOPE_INSTALLED
            );
        }

        $this->translationManager->flush();
        $this->translationManager->clear();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(Translation::class);
    }
}
