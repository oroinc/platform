<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TranslationBundle\Command\OroTranslationLoadCommand;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;
use Oro\Bundle\TranslationBundle\Translation\EmptyArrayLoader;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\Command\Stub\OutputStub;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\MessageCatalogue;

class OroTranslationLoadCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $container;

    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageProvider;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationManager;

    /** @var EmptyArrayLoader */
    protected $translationLoader;

    /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject */
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

        $language = new Language();

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->any())->method('findOneBy')->willReturn($language);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry->expects($this->any())->method('getManagerForClass')->willReturn($entityManager);
        $entityManager->expects($this->any())->method('getRepository')->willReturn($entityRepository);

        $databasePersister = $this->createMock(DatabasePersister::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['translator', 1, $this->translator],
                ['oro_translation.provider.language', 1, $this->languageProvider],
                ['oro_translation.manager.translation', 1, $this->translationManager],
                ['oro_translation.database_translation.loader', 1, $this->translationLoader],
                ['doctrine', 1, $managerRegistry],
                ['oro_translation.database_translation.persister', 1, $databasePersister],
            ]));

        $this->input = $this->createMock(InputInterface::class);
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
        $this->assertTrue($this->command->getDefinition()->hasOption('rebuild-cache'));
    }

    public function testExecute()
    {
        $this->input->expects($this->exactly(3))->method('getOption')->willReturnMap(
            [
                ['languages', []],
                ['rebuild-cache', 0]
            ]
        );

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn(['locale1' => 'locale1', 'currentLocale' => 'currentLocale']);

        $this->container->expects($this->at(2))->method('set')
            ->with('oro_translation.database_translation.loader', new EmptyArrayLoader())
            ->willReturn($this->translationManager);

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Available locales: locale1, currentLocale. Should be processed: locale1, currentLocale.',
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] ... processed 1 records.',
                '  > loading [domain2] ... processed 1 records.',
                'Loading translations [currentLocale] (1) ...',
                '  > loading [domain1] ... processed 2 records.',
                'All messages successfully processed.',
                'Done.',
            ],
            $this->output->messages
        );
    }

    public function testExecuteWithLanguageAndRebuildCache()
    {
        $this->input->expects($this->any())->method('getOption')->willReturnMap(
            [
                ['languages', ['locale1']],
                ['rebuild-cache', 1]
            ]
        );


        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguages')
            ->willReturn(['locale1' => 'locale1', 'currentLocale' => 'currentLocale']);

        $this->translator->expects($this->exactly(2))->method('rebuildCache');

        $this->command->run($this->input, $this->output);

        $this->assertEquals(
            [
                'Available locales: locale1, currentLocale. Should be processed: locale1.',
                'Loading translations [locale1] (2) ...',
                '  > loading [domain1] ... processed 1 records.',
                '  > loading [domain2] ... processed 1 records.',
                'All messages successfully processed.',
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
