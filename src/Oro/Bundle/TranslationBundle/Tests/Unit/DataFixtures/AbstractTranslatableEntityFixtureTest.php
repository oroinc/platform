<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractTranslatableEntityFixtureTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|MockObject $container */
    private $container;

    /** @var TranslatorInterface|MockObject */
    private $translator;

    /** @var ObjectManager|MockObject $objectManager */
    private $objectManager;

    protected function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();

        $this->translator = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        /** @var ObjectManager|MockObject $objectManager */
        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    protected function tearDown(): void
    {
        unset($this->fixture);
    }

    public function testSetContainerSetsContainerForLaterUse()
    {
        /** @var AbstractTranslatableEntityFixture|MockObject $fixture */
        $fixture = $this->getMockBuilder(AbstractTranslatableEntityFixture::class)->getMockForAbstractClass();
        $fixture->setContainer($this->container);

        $this->container->expects(static::once())->method('get')->with('translator');

        $fixture->load($this->objectManager);
    }

    public function testLoadSetsTranslatorFromContainer()
    {
        $this->container->expects(static::once())->method('get')->with('translator')->willReturn($this->translator);

        $fixture = new class() extends AbstractTranslatableEntityFixture {
            public function xgetTranslator(): TranslatorInterface
            {
                return $this->translator;
            }

            protected function loadEntities(ObjectManager $manager)
            {
            }
        };
        $fixture->setContainer($this->container);

        $fixture->load($this->objectManager);

        static::assertSame($this->translator, $fixture->xgetTranslator());
    }

    public function testLoadPassesManagerToLoadEntities()
    {
        /** @var AbstractTranslatableEntityFixture|MockObject $fixture */
        $fixture = $this->getMockBuilder(AbstractTranslatableEntityFixture::class)
            ->onlyMethods(['loadEntities'])
            ->getMockForAbstractClass();
        $fixture->setContainer($this->container);

        $fixture->expects(static::once())->method('loadEntities')->with(static::identicalTo($this->objectManager));

        $fixture->load($this->objectManager);
    }
}
