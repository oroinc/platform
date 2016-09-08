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

    /** @var DynamicTranslationMetadataCache */
    private $metadataCache;

    /** @var TranslationManager */
    private $translationManager;

    /** @var array */
    private $toWrite = [];

    /**
     * @param Registry $registry
     * @param TranslationManager $translationManager
     * @param DynamicTranslationMetadataCache $metadataCache
     */
    public function __construct(
        Registry $registry,
        TranslationManager $translationManager,
        DynamicTranslationMetadataCache $metadataCache
    ) {
        $this->registry = $registry;
        $this->translationManager = $translationManager;
        $this->metadataCache = $metadataCache;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->registry->getManagerForClass(Translation::ENTITY_NAME);
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
                    $this->toWrite[] = $this->getTranslationObject($key, $locale, $domain, $translation);
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
        $this->metadataCache->updateTimestamp($locale);
    }

    /**
     * Do persist into EntityManager
     *
     * @param array $items
     */
    private function write(array $items)
    {
        foreach ($items as $item) {
            $this->getEntityManager()->persist($item);
        }
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();
    }

    /**
     * Find existing translation in database
     *
     * @param string $key
     * @param string $locale
     * @param string $domain
     * @param string $value
     *
     * @return Translation
     */
    private function getTranslationObject($key, $locale, $domain, $value)
    {
        $object = $this->translationManager->findValue($key, $locale, $domain);
        if (null === $object) {
            $object = new Translation();
            $object->setScope(Translation::SCOPE_SYSTEM);
            $object->setLocale($locale);
            $object->setDomain($domain);
            $object->setKey($key);
        }

        $object->setValue($value);

        return $object;
    }
}
