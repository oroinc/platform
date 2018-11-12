<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures\LoadTranslations;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class TranslationOperationsTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadTranslations::class
        ]);
    }

    public function testUpdateCacheOperation()
    {
        $translator = $this->getTranslatorMock();
        $translator->expects($this->once())->method('rebuildCache');
        $translator->expects($this->any())->method('getTranslations')->willReturn([]);

        $this->setTranslator($translator);

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
        $translationClass = $this->getContainer()->getParameter('oro_translation.entity.translation.class');

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

    /**
     * @param Translator $translator
     */
    private function setTranslator(Translator $translator)
    {
        self::$kernel->getContainer()->set('translator.default', $translator);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Translator
     */
    private function getTranslatorMock()
    {
        return $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
    }
}
