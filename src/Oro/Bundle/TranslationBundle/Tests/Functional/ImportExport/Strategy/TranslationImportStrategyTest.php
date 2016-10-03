<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\ImportExport\Strategy\TranslationImportStrategy;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

/**
 * @dbIsolation
 */
class TranslationImportStrategyTest extends AbstractTranslationImportStrategyTest
{
    public function testProcessAdd()
    {
        $oldTranslationsCnt = $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1);
        $translation = new Translation();
        $translation
            ->setLocale(LoadLanguages::LANGUAGE1)
            ->setDomain('new_domain')
            ->setKey('new_key')
            ->setValue('new_value');

        $this->processTranslation($translation);

        $this->assertEquals($oldTranslationsCnt + 1, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));
    }

    public function testProcessReplace()
    {
        /** @var Translation $translationForReplace */
        $translationForReplace = $this->getReference(LoadTranslations::TRANSLATION1);
        $newValue = 'new_value';
        $this->assertNotEquals($newValue, $translationForReplace->getValue());
        $oldTranslationsCnt = $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1);
        $translation = new Translation();
        $translation
            ->setLocale($translationForReplace->getLocale())
            ->setDomain($translationForReplace->getDomain())
            ->setKey($translationForReplace->getKey())
            ->setValue('new_value');

        $translation = $this->processTranslation($translation);

        $this->assertEquals($oldTranslationsCnt, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));
        $this->assertEquals($newValue, $translationForReplace->getValue());
        $this->assertEquals($translationForReplace->getId(), $translation->getId());
    }

    /**
     * {@inheritdoc}
     */
    protected function getStrategyObject()
    {
        $container = $this->getContainer();

        return new TranslationImportStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.import.helper'),
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper')
        );
    }
}
