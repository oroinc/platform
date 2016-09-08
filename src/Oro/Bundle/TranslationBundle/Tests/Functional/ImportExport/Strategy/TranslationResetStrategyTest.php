<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\ImportExport\Strategy\TranslationResetStrategy;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadLanguages;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

/**
 * @dbIsolation
 */
class TranslatioResetStrategyTest extends AbstractTranslationImportStrategyTest
{
    public function testProcess()
    {
        $this->assertGreaterThan(1, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));

        /** @var Translation $existingTranslation */
        $existingTranslation = $this->getReference(LoadTranslations::TRANSLATION1);
        $translation = new Translation();
        $translation
            ->setLocale($existingTranslation->getLocale())
            ->setDomain($existingTranslation->getDomain())
            ->setKey($existingTranslation->getKey())
            ->setValue($existingTranslation->getValue());

        $this->processTranslation($translation);

        $this->assertEquals(1, $this->getTranslationsByLocaleCount(LoadLanguages::LANGUAGE1));

        $translation = new Translation();
        $translation
            ->setLocale(LoadLanguages::LANGUAGE1)
            ->setDomain('new_domain')
            ->setKey('new_key')
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
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper')
        );
    }
}
