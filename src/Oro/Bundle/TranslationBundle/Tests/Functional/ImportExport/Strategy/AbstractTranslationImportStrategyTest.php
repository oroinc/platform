<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

abstract class AbstractTranslationImportStrategyTest extends WebTestCase
{
    /**
     * @var AbstractImportStrategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadTranslations::class,
            ]
        );

        $this->strategy = $this->getStrategyObject();
        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName(Translation::class);
    }

    protected function tearDown()
    {
        unset($this->strategy);
    }

    /**
     * @param string $locale
     * @return int
     */
    protected function getTranslationsByLocaleCount($locale)
    {
        $language = $this->getReference($locale);

        return count($this->getEntityManager()->getRepository(Translation::class)->findBy(['language' => $language]));
    }

    /**
     * @param Translation $translation
     * @return Translation
     */
    protected function processTranslation(Translation $translation)
    {
        $translation = $this->strategy->process($translation);

        $em = $this->getEntityManager();
        $em->persist($translation);
        $em->flush();

        return $translation;
    }

    /**
     * @param TranslationKey $translationKey
     *
     * @return TranslationKey
     */
    protected function processTranslationKey(TranslationKey $translationKey)
    {
        $em = $this->getEntityManager();
        $em->persist($translationKey);
        $em->flush();

        return $translationKey;
    }

    /**
     * @return ObjectManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(Translation::class);
    }

    /**
     * @return AbstractImportStrategy
     */
    abstract protected function getStrategyObject();
}
