<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\DataFixtures\AbstractTranslatableEntityFixture;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractTranslatableEntityFixtureTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $objectManager;

    protected function setUp(): void
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)
            ->onlyMethods(['get'])
            ->getMockForAbstractClass();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function testSetContainerSetsContainerForLaterUse()
    {
        $fixture = $this->getMockForAbstractClass(AbstractTranslatableEntityFixture::class);
        $fixture->setContainer($this->container);

        $this->container->expects(self::once())
            ->method('get')
            ->with('translator');

        $fixture->load($this->objectManager);
    }

    public function testLoadSetsTranslatorFromContainer()
    {
        $this->container->expects(self::once())
            ->method('get')->with('translator')
            ->willReturn($this->translator);

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

        self::assertSame($this->translator, $fixture->xgetTranslator());
    }

    public function testLoadPassesManagerToLoadEntities()
    {
        $fixture = $this->getMockBuilder(AbstractTranslatableEntityFixture::class)
            ->onlyMethods(['loadEntities'])
            ->getMockForAbstractClass();
        $fixture->setContainer($this->container);

        $fixture->expects(self::once())
            ->method('loadEntities')
            ->with(self::identicalTo($this->objectManager));

        $fixture->load($this->objectManager);
    }
}
