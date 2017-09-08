<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\ImportExport\Strategy\TranslationImportStrategy;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationImportStrategyTest extends AbstractTranslationImportStrategyTest
{
    public function testProcessAdd()
    {
        $oldTranslationsCnt = $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1);
        $translation = new Translation();
        $translationKey = (new TranslationKey())->setKey('new_key')->setDomain('new_domain');
        $translationKey = $this->processTranslationKey($translationKey);

        $translation
            ->setLanguage($this->getReference(LoadLanguages::LANGUAGE1))
            ->setTranslationKey($translationKey)
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
            ->setLanguage($translationForReplace->getLanguage())
            ->setTranslationKey($translationForReplace->getTranslationKey())
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
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_security.owner.checker')
        );
    }
}
