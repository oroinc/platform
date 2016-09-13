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

    /** @var EntityManager */
    private $em;

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
    public function __construct(
        Registry $registry,
        TranslationManager $translationManager
    ) {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->registry->getManagerForClass(Translation::class);
        }

        return $this->em;
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
        try {
            $this->getEntityManager()->beginTransaction();
            foreach ($data as $domain => $domainData) {
                foreach ($domainData as $key => $translation) {
                    if (strlen($key) > MySqlPlatform::LENGTH_LIMIT_TINYTEXT) {
                        continue;
                    }

                    $writeCount++;
                    $this->toWrite[] = $this->translationManager->saveValue($key, $translation, $locale, $domain);
                    if (0 === $writeCount % $this->batchSize) {
                        $this->write($this->toWrite);

                        $this->toWrite = [];
                    }
                }
            }

            if (count($this->toWrite) > 0) {
                $this->write($this->toWrite);
            }

            $this->getEntityManager()->commit();
        } catch (\Exception $exception) {
            $this->getEntityManager()->rollback();

            throw $exception;
        }

        // update timestamp in case when persist succeed
        $this->translationManager->invalidateCache($locale);
    }

    /**
     * Writes all changes to DataBase
     */
    private function write()
    {
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }
}
