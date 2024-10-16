<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\Cache\EnumTranslationCache;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;

abstract class EnumValueListenerTestCase extends \PHPUnit\Framework\TestCase
{
    protected const ENUM_VALUE_CLASS = TestEnumValue::class;
    protected const ENUM_CODE = 'test_enum_code';

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var EnumTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationManagers;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationHelper;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $messageProducer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(EnumTranslationCache::class);
        $this->translationManager = $this->createMock(TranslationManager::class);
        $this->translationHelper = $this->createMock(ConfigTranslationHelper::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->em = $this->createMock(EntityManager::class);

        $this->translationHelper->expects($this->any())->method('getLocale')->willReturn('fr');
        $this->doctrine->expects($this->any())->method('getManagerForClass')->willReturn($this->em);
    }

    protected function assertClearCacheCalled()
    {
        $this->cache->expects($this->once())
            ->method('invalidate')
            ->with(self::ENUM_CODE);
    }

    protected function assetTranslationRemoved()
    {
        $repo = $this->createMock(TranslationRepository::class);
        $this->em
            ->expects($this->once())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($repo);
        $repo
            ->expects($this->once())
            ->method('findTranslation')
            ->willReturn((new Translation()));
        $this->em
            ->expects($this->once())
            ->method('remove');
    }

    protected function assertClearCacheNotCalled()
    {
        $this->cache->expects($this->never())
            ->method('invalidate');
    }
}
