<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\TranslatorStub;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationOperationsTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadTranslations::class
        ]);
    }

    public function testUpdateCacheOperation()
    {
        $translatorMock = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $translatorMock->expects($this->once())->method('rebuildCache');
        $translatorMock->expects($this->any())->method('getTranslations')->willReturn([]);

        /** @var TranslatorStub $translator */
        $translator = $this->getContainer()->get('translator.default');
        $translator->setRebuildCache([$translatorMock, 'rebuildCache']);
        $translator->setGetTranslations([$translatorMock, 'getTranslations']);

        $this->assertExecuteOperation(
            'oro_translation_rebuild_cache',
            null,
            null,
            ['route' => 'oro_translation_translation_index']
        );
    }

    /**
     * @dataProvider removeTranslationOperationDataProvider
     *
     * @param string $translation
     */
    public function testRemoveTranslationOperation($translation)
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
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(true, $response['success']);
        $this->assertContains("oro-translation-translations-grid", $response['refreshGrid']);
        $removedTranslation = self::getContainer()
            ->get('doctrine')
            ->getRepository($translationClass)
            ->find($entityId);

        static::assertNull($removedTranslation);
    }

    /**
     * @return array
     */
    public function removeTranslationOperationDataProvider()
    {
        return [
            'scope SYSTEM' => [LoadTranslations::TRANSLATION_KEY_1],
            'scope INSTALLED' => [LoadTranslations::TRANSLATION_KEY_4],
            'scope UI' => [LoadTranslations::TRANSLATION_KEY_5]
        ];
    }
}
