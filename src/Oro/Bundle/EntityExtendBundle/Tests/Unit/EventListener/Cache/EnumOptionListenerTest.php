<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\EventListener\Cache\EnumOptionListener;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnumOptionListenerTest extends TestCase
{
    private const ENUM_CODE = 'test_enum_code';

    private EntityManager&MockObject $em;
    private EnumTranslationCache&MockObject $cache;
    private TranslationManager&MockObject $translationManager;
    private MessageProducerInterface&MockObject $messageProducer;
    private EnumOptionListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->cache = $this->createMock(EnumTranslationCache::class);
        $this->translationManager = $this->createMock(TranslationManager::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $translationHelper = $this->createMock(ConfigTranslationHelper::class);
        $translationHelper->expects(self::any())
            ->method('getLocale')
            ->willReturn('fr');

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->listener = new EnumOptionListener(
            $doctrine,
            $this->cache,
            $this->translationManager,
            $translationHelper,
            $this->messageProducer
        );
        EntityExtendTestInitializer::initialize();
    }

    private function getEntityInstance(): TestEnumValue
    {
        return new TestEnumValue(self::ENUM_CODE, 'Test', 'test', 1);
    }

    public function testPostPersist(): void
    {
        $this->cache->expects(self::once())
            ->method('invalidate')
            ->with(self::ENUM_CODE);

        $this->listener->postPersist($this->getEntityInstance());
    }

    public function testPostPersistNotSupportedClass(): void
    {
        $this->cache->expects(self::never())
            ->method('invalidate');

        $this->listener->postPersist(new \stdClass());
    }

    public function testPostUpdate(): void
    {
        $this->cache->expects(self::once())
            ->method('invalidate')
            ->with(self::ENUM_CODE);

        $this->listener->postUpdate($this->getEntityInstance());
    }

    public function testPostUpdateNotSupportedClass(): void
    {
        $this->cache->expects(self::never())
            ->method('invalidate');

        $this->listener->postUpdate(new \stdClass());
    }

    public function testPostRemove(): void
    {
        $this->cache->expects(self::once())
            ->method('invalidate')
            ->with(self::ENUM_CODE);

        $this->listener->postRemove($this->getEntityInstance());
    }

    public function testPostFlush(): void
    {
        $repo = $this->createMock(TranslationRepository::class);
        $this->em->expects(self::once())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($repo);
        $repo->expects(self::once())
            ->method('findTranslation')
            ->willReturn((new Translation()));
        $this->em->expects(self::once())
            ->method('remove');

        $this->cache->expects(self::once())
            ->method('invalidate')
            ->with(self::ENUM_CODE);

        $this->listener->postRemove($this->getEntityInstance());
        $this->listener->postFlush();
    }

    public function testPostRemoveNotSupportedClass(): void
    {
        $this->cache->expects(self::never())
            ->method('invalidate');

        $this->listener->postRemove(new \stdClass());
    }
}
