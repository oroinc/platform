<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

class DatabasePersister
{
    /** @var int */
    private $batchSize = 200;

    /** @var EntityManager */
    private $em;

    /** @var TranslationRepository */
    private $repository;

    /** @var array */
    private $toWrite = [];

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em         = $em;
        $this->repository = $em->getRepository(Translation::ENTITY_NAME);
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
            $this->em->beginTransaction();
            foreach ($data as $domain => $domainData) {
                foreach ($domainData as $key => $translation) {
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

            $this->em->commit();
            $this->em->clear();
        } catch (\Exception $exception) {
            $this->em->rollback();
            $this->em->clear();

            throw $exception;
        }
    }

    /**
     * Do persist into EntityManager
     *
     * @param array $items
     */
    private function write(array $items)
    {
        foreach ($items as $item) {
            $this->em->persist($item);
        }
        $this->em->flush();
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
        $object = $this->repository->findValue($key, $locale, $domain);
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
