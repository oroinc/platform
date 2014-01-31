<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Translation;

class DatabasePersister
{
    const BATCH_SIZE = 100;

    /** @var EntityManager */
    private $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param string $locale
     * @param array  $data translations strings, format same as MassageCatalog::all() returns
     *
     * @throws \Exception
     */
    public function persist($locale, array $data)
    {
        $repo = $this->em->getRepository(Translation::ENTITY_NAME);

        $itemsToWrite = [];
        $writeCount   = 0;

        try {
            $this->em->beginTransaction();
            foreach ($data as $domain => $domainData) {
                $countPersisted = 0;

                foreach ($domainData as $key => $translation) {
                    $countPersisted++;

                    $object = $repo->findValue($key, $locale, $domain);
                    if (null === $object) {
                        $object = new Translation();
                        $object->setScope(Translation::SCOPE_SYSTEM);
                        $object->setLocale($locale);
                        $object->setDomain($domain);
                        $object->setKey($key);
                    }

                    $object->setValue($translation);

                    $writeCount++;
                    $itemsToWrite[] = $object;
                    if (0 === $writeCount % self::BATCH_SIZE) {
                        $this->write($itemsToWrite);

                        $itemsToWrite = [];
                    }
                }
            }

            if (count($itemsToWrite) > 0) {
                $this->write($itemsToWrite);
            }

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();

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
        $this->em->clear();
    }
}
