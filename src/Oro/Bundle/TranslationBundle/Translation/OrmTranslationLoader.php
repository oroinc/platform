<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;

class OrmTranslationLoader implements LoaderInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        if ($this->checkDatabase()) {
            $messages = [];
            /** @var TranslationRepository $translationRepo */
            $translationRepo = $this->em->getRepository(Translation::ENTITY_NAME);
            /** @var Translation[] $translations */
            $translations = $translationRepo->findValues($locale);
            foreach ($translations as $translation) {
                $messages[$translation->getKey()] = $translation->getValue();
            }

            $catalogue->add($messages, $domain);
        }

        return $catalogue;
    }

    /**
     * Check if translations table exists in db
     *
     * @return bool
     */
    protected function checkDatabase()
    {
        $tableName = $this->em->getClassMetadata(Translation::ENTITY_NAME)->getTableName();
        $result    = false;
        try {
            $conn = $this->em->getConnection();

            if (!$conn->isConnected()) {
                $this->em->getConnection()->connect();
            }

            $result = $conn->isConnected()
                && (bool)array_intersect(
                    array($tableName),
                    $this->em->getConnection()->getSchemaManager()->listTableNames()
                );
        } catch (\PDOException $e) {
        }

        return $result;
    }
}
