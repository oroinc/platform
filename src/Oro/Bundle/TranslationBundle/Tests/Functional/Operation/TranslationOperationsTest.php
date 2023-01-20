<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;

/**
 * @dbIsolationPerTest
 */
class TranslationOperationsTest extends ActionTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadTranslations::class
        ]);
    }

    /**
     * @dataProvider removeTranslationOperationDataProvider
     */
    public function testRemoveTranslationOperation(string $translation)
    {
        $translation = $this->getReference($translation);
        $translationClass = Translation::class;

        $entityId = $translation->getId();
        $this->assertExecuteOperation(
            'oro_translation_translation_reset',
            $entityId,
            $translationClass,
            ['datagrid' => 'oro-translation-translations-grid', 'group' => ['datagridRowAction']]
        );
        $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(true, $response['success']);
        $this->assertContains('oro-translation-translations-grid', $response['refreshGrid']);
        $removedTranslation = self::getContainer()
            ->get('doctrine')
            ->getRepository($translationClass)
            ->find($entityId);

        self::assertNull($removedTranslation);
    }

    public function removeTranslationOperationDataProvider(): array
    {
        return [
            'scope SYSTEM' => [LoadTranslations::TRANSLATION_KEY_1],
            'scope INSTALLED' => [LoadTranslations::TRANSLATION_KEY_4],
            'scope UI' => [LoadTranslations::TRANSLATION_KEY_5]
        ];
    }
}
