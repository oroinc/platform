<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Cache\RebuildTranslationCacheHandlerInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Tests\Functional\Stub\RebuildTranslationCacheHandlerStub;

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

    private function getRebuildTranslationCacheHandlerStub(): RebuildTranslationCacheHandlerStub
    {
        return self::getContainer()->get('oro_translation.rebuild_translation_cache_handler');
    }

    public function testUpdateCacheOperation()
    {
        $handlerMock = $this->createMock(RebuildTranslationCacheHandlerInterface::class);
        $handlerMock->expects($this->once())
            ->method('rebuildCache');

        $handlerStub = $this->getRebuildTranslationCacheHandlerStub();
        $handlerStub->setRebuildCache([$handlerMock, 'rebuildCache']);
        try {
            $this->assertExecuteOperation(
                'oro_translation_rebuild_cache',
                null,
                null,
                ['route' => 'oro_translation_translation_index']
            );
        } finally {
            $handlerStub->setRebuildCache(null);
        }
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
