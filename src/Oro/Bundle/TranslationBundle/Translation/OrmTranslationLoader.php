<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

class OrmTranslationLoader implements LoaderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DatabaseChecker */
    protected $databaseChecker;

    /**
     * @param ManagerRegistry $doctrine
     * @param DatabaseChecker $databaseChecker
     */
    public function __construct(ManagerRegistry $doctrine, DatabaseChecker $databaseChecker)
    {
        $this->doctrine = $doctrine;
        $this->databaseChecker = $databaseChecker;
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
            $translations = $this->getTranslationRepository()->findAllByLanguageAndDomain($locale, $domain);
            foreach ($translations as $translation) {
                $messages[$translation['key']] = $translation['value'];
            }

            $catalogue->add($messages, $domain);
        }

        return $catalogue;
    }

    /**
     * Checks whether the translations table exists in the database
     *
     * @return bool
     */
    protected function checkDatabase()
    {
        return $this->databaseChecker->checkDatabase();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManagerForClass(Translation::class);
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository()
    {
        return $this->getEntityManager()->getRepository(Translation::class);
    }
}
