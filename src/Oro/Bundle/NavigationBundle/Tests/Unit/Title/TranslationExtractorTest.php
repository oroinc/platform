<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Title;

use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Oro\Bundle\NavigationBundle\Title\TranslationExtractor;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Component\Translation\MessageCatalogue;

class TranslationExtractorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleReaderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $titleReaderRegistry;

    /** @var ControllerClassProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $controllerClassProvider;

    /** @var TranslationExtractor */
    private $translatorExtractor;

    protected function setUp(): void
    {
        $this->titleReaderRegistry = $this->createMock(TitleReaderRegistry::class);
        $this->controllerClassProvider = $this->createMock(ControllerClassProvider::class);

        $this->translatorExtractor = new TranslationExtractor(
            $this->titleReaderRegistry,
            $this->controllerClassProvider
        );
    }

    public function testExtract()
    {
        $controllers = ['route_1' => ['TestBundle/Controller/TestController', 'testAction']];

        $this->controllerClassProvider->expects($this->once())
            ->method('getControllers')
            ->willReturn($controllers);

        $this->titleReaderRegistry->expects($this->once())
            ->method('getTitleByRoute')
            ->with('route_1')
            ->willReturn('test.title');

        $catalogue = $this->createMock(MessageCatalogue::class);
        $catalogue->expects($this->once())
            ->method('set')
            ->with('test.title', 'prefix_test.title');

        $this->translatorExtractor->setPrefix('prefix_');
        $this->translatorExtractor->extract('TestBundle', $catalogue);
    }
}
