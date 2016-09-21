<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Oro\Bundle\TranslationBundle\Command\OroTranslationLoadCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Translator;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Tests\Unit\Command\Stubs\OutputStub;

class OroTranslationLoadCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

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

        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->getMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['translator', 1, $this->translator],
                ['oro_translation.manager.translation', 1, $this->translationManager],
            ]));

        $this->input = $this->getMock(InputInterface::class);
        $this->output = new OutputStub();

        $this->command = new OroTranslationLoadCommand();
        $this->command->setContainer($this->container);
    }

    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
        $this->assertTrue($this->command->getDefinition()->hasOption('languages'));
    }

    public function testExecute()
    {
        $this->translator->expects($this->once())->method('getFallbackLocales')->willReturn(['locale1']);
        $this->translator->expects($this->once())->method('getLocale')->willReturn('currentLocale');

        $this->translationManager->expects($this->at(0))->method('findValue')
            ->with('key1', 'locale1', 'domain1');
        $this->translationManager->expects($this->at(1))->method('createValue')
            ->with('key1', 'domain1-locale1-message1', 'locale1', 'domain1', true);
        $this->translationManager->expects($this->at(2))->method('flush');


        $this->translationManager->expects($this->at(3))->method('findValue')
            ->with('key1', 'locale1', 'domain2')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(4))->method('flush');

        $this->translationManager->expects($this->at(5))->method('findValue')
            ->with('key1', 'currentLocale', 'domain1')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(6))->method('findValue')
            ->with('key2', 'currentLocale', 'domain1')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(7))->method('flush');

        $this->translationManager->expects($this->at(8))->method('invalidateCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] (1) ... ',
                'added 1 records.',
                '  > loading [domain2] (1) ... ',
                'added 0 records.',
                'Loading translations [currentLocale] (1) ...',
                '  > loading [domain1] (2) ... ',
                'added 0 records.',
                'All messages successfully loaded.',
            ],
            $this->output->messages
        );
    }

    public function testExecuteWithLanguage()
    {
        $this->input->expects($this->once())->method('getOption')->with('languages')->willReturn(['locale1']);

        $this->translator->expects($this->never())->method('getFallbackLocales');
        $this->translator->expects($this->never())->method('getLocale');

        $this->translationManager->expects($this->at(0))->method('findValue')
            ->with('key1', 'locale1', 'domain1');
        $this->translationManager->expects($this->at(1))->method('createValue')
            ->with('key1', 'domain1-locale1-message1', 'locale1', 'domain1', true);
        $this->translationManager->expects($this->at(2))->method('flush');


        $this->translationManager->expects($this->at(3))->method('findValue')
            ->with('key1', 'locale1', 'domain2')->willReturn(new \stdClass());
        $this->translationManager->expects($this->at(4))->method('flush');

        $this->translationManager->expects($this->at(5))->method('invalidateCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] (1) ... ',
                'added 1 records.',
                '  > loading [domain2] (1) ... ',
                'added 0 records.',
                'All messages successfully loaded.',
            ],
            $this->output->messages
        );
    }

    public function testExecuteWithUnknownLanguage()
    {
        $this->input->expects($this->once())->method('getOption')->with('languages')->willReturn(['unknown_locale']);

        $this->translator->expects($this->never())->method('getFallbackLocales');
        $this->translator->expects($this->never())->method('getLocale');

        $this->translationManager->expects($this->never())->method('findValue');
        $this->translationManager->expects($this->never())->method('createValue');
        $this->translationManager->expects($this->never())->method('flush');

        $this->translationManager->expects($this->at(0))->method('invalidateCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Loading translations [unknown_locale] (0) ...',
                'All messages successfully loaded.',
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
            [
                'unknown_locale',
                new MessageCatalogue('unknown_locale', [])
            ],
        ];
    }
}
