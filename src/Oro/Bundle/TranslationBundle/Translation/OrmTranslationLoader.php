<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * Loads translations
 */
class OrmTranslationLoader implements LoaderInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var DatabaseChecker */
    protected $databaseChecker;

    /** @var bool Determines whatever load method will process all translations. There is cases when it should not.
     * Otherwise empty MessageCatalogue will be returned.
     */
    private $enabled = true;

    public function __construct(ManagerRegistry $doctrine, DatabaseChecker $databaseChecker)
    {
        $this->doctrine = $doctrine;
        $this->databaseChecker = $databaseChecker;
    }

    /**
     * @return $this
     */
    public function setDisabled()
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function setEnabled()
    {
        $this->enabled = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        /** @var MessageCatalogue $catalogue */
        $catalogue = new MessageCatalogue($locale);

        if ($this->enabled && $this->checkDatabase()) {
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
