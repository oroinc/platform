<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\ImportExport\Strategy\TranslationResetStrategy;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

class TranslationResetStrategyTest extends AbstractTranslationImportStrategyTest
{
    public function testProcess()
    {
        $this->assertGreaterThan(1, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));

        /** @var Translation $existingTranslation */
        $existingTranslation = $this->getReference(LoadTranslations::TRANSLATION1);
        $translation = new Translation();
        $translation
            ->setLanguage($existingTranslation->getLanguage())
            ->setTranslationKey($existingTranslation->getTranslationKey())
            ->setValue($existingTranslation->getValue());

        $this->processTranslation($translation);

        $this->assertEquals(1, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));

        $translation = new Translation();
        $translationKey = new TranslationKey();
        $translationKey = $this->processTranslationKey($translationKey->setDomain('new_domain')->setKey('new_key'));

        $translation
            ->setLanguage($this->getReference(LoadLanguages::LANGUAGE1))
            ->setTranslationKey($translationKey)
            ->setValue('new_value');

        $this->processTranslation($translation);

        $this->assertEquals(2, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));
    }

    /**
     * {@inheritdoc}
     */
    protected function getStrategyObject()
    {
        $container = $this->getContainer();

        return new TranslationResetStrategy(
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
