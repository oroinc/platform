<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TranslationBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class TranslationOperationsTest extends ActionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
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
     * @param Translator $translator
     */
    private function setTranslator(Translator $translator)
    {
        self::$kernel->getContainer()->set('translator.default', $translator);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Translator
     */
    private function getTranslatorMock()
    {
        return $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
    }
}
