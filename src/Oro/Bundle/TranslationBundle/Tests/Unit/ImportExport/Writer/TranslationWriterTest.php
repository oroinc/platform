<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\ImportExport\Writer\TranslationWriter;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $translationManager;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var TranslationWriter */
    private $writer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->translationManager = $this->createMock(TranslationManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($this->entityManager);

        $this->writer = new TranslationWriter($this->registry, $this->translationManager);
    }

    public function testWrite()
    {
        $items = [
            $this->getTranslation('key1', 'value1', 'domain1', 'lang1'),
            $this->getTranslation('key2', 'value2', 'domain2', 'lang2'),
            $this->getTranslation('key3', 'value3', 'domain3', 'lang3'),
        ];

        $calls = [];

        $this->entityManager->expects($this->once())
            ->method('beginTransaction')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'beginTransaction';
            });
        $this->entityManager->expects($this->once())
            ->method('commit')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'commit';
            });
        $this->entityManager->expects($this->once())
            ->method('clear')
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'clear';
            });

        $this->translationManager->expects($this->exactly(3))
            ->method('saveTranslation')
            ->withConsecutive(
                ['key1', 'value1', 'lang1', 'domain1', Translation::SCOPE_UI],
                ['key2', 'value2', 'lang2', 'domain2', Translation::SCOPE_UI],
                ['key3', 'value3', 'lang3', 'domain3', Translation::SCOPE_UI]
            )
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'saveTranslation';
            });
        $this->translationManager->expects($this->once())
            ->method('flush')
            ->with(true)
            ->willReturnCallback(function () use (&$calls) {
                $calls[] = 'flush';
            });

        $this->writer->write($items);

        self::assertEquals(
            [
                'beginTransaction',
                'saveTranslation',
                'saveTranslation',
                'saveTranslation',
                'flush',
                'commit',
                'clear'
            ],
            $calls
        );
    }

    public function testWriteWithException()
    {
        $items = [$this->getTranslation('key1', 'value1', 'domain1', 'lang1')];

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->once())
            ->method('rollback');

        $exception = new \Exception('test exception');

        $this->translationManager->expects($this->once())
            ->method('saveTranslation')
            ->willThrowException($exception);
        $this->translationManager->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test exception');

        $this->writer->write($items);
    }

    public function testWriteWithExceptionAndClosedEntityManager()
    {
        $items = [$this->getTranslation('key1', 'value1', 'domain1', 'lang1')];

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->once())
            ->method('rollback');
        $this->entityManager->expects($this->once())
            ->method('isOpen')
            ->willReturn(false);

        $this->registry->expects($this->once())
            ->method('resetManager');

        $exception = new \Exception('test exception');

        $this->translationManager->expects($this->once())
            ->method('saveTranslation')
            ->willThrowException($exception);
        $this->translationManager->expects($this->never())
            ->method('flush');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test exception');

        $this->writer->write($items);
    }

    private function getTranslation(string $key, string $value, string $domain, string $languageCode): Translation
    {
        $translation = new Translation();
        $translation->setValue($value)
            ->setLanguage($this->getLanguage($languageCode))
            ->setTranslationKey($this->getTranslationKey($key, $domain));

        return $translation;
    }

    private function getLanguage(string $code): Language
    {
        $language = new Language();
        $language->setCode($code);

        return $language;
    }

    private function getTranslationKey(string $key, string $domain): TranslationKey
    {
        $translationKey = new TranslationKey();
        $translationKey->setKey($key)->setDomain($domain);

        return $translationKey;
    }
}
