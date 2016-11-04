<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Command\OroTranslationLoadCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs\OutputStub;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class OroTranslationLoadCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var LanguageProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $languageProvider;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

    /** @var EmptyArrayLoader */
    protected $translationLoader;

    /** @var InputInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $input;

    /** @var OutputStub */
    protected $output;

    /** @var OroTranslationLoadCommand */
    protected $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator->expects($this->any())
            ->method('getCatalogue')
            ->will($this->returnValueMap($this->getCatalogueMap()));

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationLoader = new EmptyArrayLoader();

        $this->container = $this->getMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['translator', 1, $this->translator],
                ['oro_translation.provider.language', 1, $this->languageProvider],
                ['oro_translation.manager.translation', 1, $this->translationManager],
                ['oro_translation.database_translation.loader', 1, $this->translationLoader],
            ]));

        $this->input = $this->getMock(InputInterface::class);
        $this->output = new OutputStub();

        $this->command = new OroTranslationLoadCommand();
        $this->command->setContainer($this->container);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->translator,
            $this->languageProvider,
            $this->translationManager,
            $this->translationLoader,
            $this->container,
            $this->input,
            $this->output,
            $this->command
        );
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasOption('languages'));
    }

    public function testExecute()
    {
        $this->input->expects($this->once())->method('getOption')->with('languages')->willReturn([]);

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn(['locale1' => 'locale1', 'currentLocale' => 'currentLocale']);

        $loader = new EmptyArrayLoader();

        $this->container->expects($this->at(2))->method('set')
            ->with('oro_translation.database_translation.loader', new EmptyArrayLoader())
            ->willReturn($this->translationManager);

        $this->translator->expects($this->at(0))->method('rebuildCache');

        $this->translationManager->expects($this->at(0))->method('saveTranslation')
            ->with('key1', 'domain1-locale1-message1', 'locale1', 'domain1');
        $this->translationManager->expects($this->at(1))->method('flush');
        $this->translationManager->expects($this->at(2))->method('clear');

        $this->translationManager->expects($this->at(3))->method('saveTranslation')
            ->with('key1', 'domain2-locale1-message1', 'locale1', 'domain2')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(4))->method('flush');
        $this->translationManager->expects($this->at(5))->method('clear');

        $this->translationManager->expects($this->at(6))->method('saveTranslation')
            ->with('key1', 'domain1-currentLocale-message1', 'currentLocale', 'domain1')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(7))->method('saveTranslation')
            ->with('key2', 'domain1-currentLocale-message2', 'currentLocale', 'domain1')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(8))->method('flush');
        $this->translationManager->expects($this->at(9))->method('clear');

        $this->container->expects($this->at(5))->method('set')
            ->with('oro_translation.database_translation.loader', $loader);

        $this->translator->expects($this->at(1))->method('rebuildCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Available locales: locale1, currentLocale. Should be processed: locale1, currentLocale.',
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] (1) ... ',
                'processed 1 records.',
                '  > loading [domain2] (1) ... ',
                'processed 1 records.',
                'Loading translations [currentLocale] (1) ...',
                '  > loading [domain1] (2) ... ',
                'processed 2 records.',
                'All messages successfully loaded.',
                'Rebuilding cache ... ',
                'Done.',
            ],
            $this->output->messages
        );
    }

    public function testExecuteWithLanguage()
    {
        $this->input->expects($this->once())->method('getOption')->with('languages')->willReturn(['locale1']);

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn(['locale1' => 'locale1', 'currentLocale' => 'currentLocale']);

        $this->translator->expects($this->at(0))->method('rebuildCache');

        $this->translationManager->expects($this->at(0))->method('saveTranslation')
            ->with('key1', 'domain1-locale1-message1', 'locale1', 'domain1');
        $this->translationManager->expects($this->at(1))->method('flush');
        $this->translationManager->expects($this->at(2))->method('clear');

        $this->translationManager->expects($this->at(3))->method('saveTranslation')
            ->with('key1', 'domain2-locale1-message1', 'locale1', 'domain2')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(4))->method('flush');
        $this->translationManager->expects($this->at(5))->method('clear');

        $this->translator->expects($this->at(1))->method('rebuildCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Available locales: locale1, currentLocale. Should be processed: locale1.',
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] (1) ... ',
                'processed 1 records.',
                '  > loading [domain2] (1) ... ',
                'processed 1 records.',
                'All messages successfully loaded.',
                'Rebuilding cache ... ',
                'Done.',
            ],
            $this->output->messages
        );
    }

    /**
     * @return array
     */
    protected function getCatalogueMap()
    {
        return [
            [
                'currentLocale',
                new MessageCatalogue('currentLocale', [
                    'domain1' => [
                        'key1' => 'domain1-currentLocale-message1',
                        'key2' => 'domain1-currentLocale-message2',
                    ],
                ])
            ],
            [
                'locale1',
                new MessageCatalogue('locale1', [
                    'domain1' => [
                        'key1' => 'domain1-locale1-message1',
                    ],
                    'domain2' => [
                        'key1' => 'domain2-locale1-message1',
                    ],
                ])
            ],
        ];
    }
}
