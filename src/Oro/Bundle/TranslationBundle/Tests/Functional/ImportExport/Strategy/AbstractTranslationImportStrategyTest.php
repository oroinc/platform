<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

abstract class AbstractTranslationImportStrategyTest extends WebTestCase
{
    /** @var AbstractImportStrategy */
    private $strategy;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadTranslations::class]);

        $this->strategy = $this->getStrategyObject();
        $this->strategy->setImportExportContext(new Context([]));
        $this->strategy->setEntityName(Translation::class);
    }

    protected function getTranslationsByLocaleCount(string $locale): int
    {
        $language = $this->getReference($locale);

        return count($this->getEntityManager()->getRepository(Translation::class)->findBy(['language' => $language]));
    }

    protected function processTranslation(Translation $translation): Translation
    {
        $translation = $this->strategy->process($translation);

        $em = $this->getEntityManager();
        $em->persist($translation);
        $em->flush();

        return $translation;
    }

    protected function processTranslationKey(TranslationKey $translationKey): TranslationKey
    {
        $em = $this->getEntityManager();
        $em->persist($translationKey);
        $em->flush();

        return $translationKey;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Translation::class);
    }

    abstract protected function getStrategyObject(): AbstractImportStrategy;
}
