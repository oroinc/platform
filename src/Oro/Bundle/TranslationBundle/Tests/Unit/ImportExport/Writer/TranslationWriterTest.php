<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\ImportExport\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\ImportExport\Writer\TranslationWriter;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

class TranslationWriterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationManager;

    /** @var TranslationWriter */
    protected $writer;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($this->entityManager);

        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->writer = new TranslationWriter($this->registry, $this->translationManager);
    }

    protected function tearDown()
    {
        unset($this->writer, $this->entityManager, $this->registry, $this->translationManager);
    }

    public function testWrite()
    {
        $items = [
            $this->getTranslation('key1', 'value1', 'domain1', 'lang1'),
            $this->getTranslation('key2', 'value2', 'domain2', 'lang2'),
            $this->getTranslation('key3', 'value3', 'domain3', 'lang3'),
        ];

        $this->entityManager->expects($this->at(0))->method('beginTransaction');
        $this->entityManager->expects($this->at(1))->method('commit');
        $this->entityManager->expects($this->at(2))->method('clear');

        $this->translationManager->expects($this->at(0))
            ->method('saveTranslation')
            ->with('key1', 'value1', 'lang1', 'domain1', Translation::SCOPE_UI);
        $this->translationManager->expects($this->at(1))
            ->method('saveTranslation')
            ->with('key2', 'value2', 'lang2', 'domain2', Translation::SCOPE_UI);
        $this->translationManager->expects($this->at(2))
            ->method('saveTranslation')
            ->with('key3', 'value3', 'lang3', 'domain3', Translation::SCOPE_UI);
        $this->translationManager->expects($this->at(3))->method('flush')->with(true);

        $this->writer->write($items);
    }

    public function testWriteWithException()
    {
        $items = [$this->getTranslation('key1', 'value1', 'domain1', 'lang1')];

        $this->entityManager->expects($this->at(0))->method('beginTransaction');
        $this->entityManager->expects($this->at(1))->method('rollback');

        $exception = new \Exception('test exception');

        $this->translationManager->expects($this->once())->method('saveTranslation')->willThrowException($exception);
        $this->translationManager->expects($this->never())->method('flush');

        $this->expectException('Exception');
        $this->expectExceptionMessage('test exception');

        $this->writer->write($items);
    }

    public function testWriteWithExceptionAndClosedEntityManager()
    {
        $items = [$this->getTranslation('key1', 'value1', 'domain1', 'lang1')];

        $this->entityManager->expects($this->at(0))->method('beginTransaction');
        $this->entityManager->expects($this->at(1))->method('rollback');
        $this->entityManager->expects($this->at(2))->method('isOpen')->willReturn(false);

        $this->registry->expects($this->once())->method('resetManager');

        $exception = new \Exception('test exception');

        $this->translationManager->expects($this->once())->method('saveTranslation')->willThrowException($exception);
        $this->translationManager->expects($this->never())->method('flush');

        $this->expectException('Exception');
        $this->expectExceptionMessage('test exception');

        $this->writer->write($items);
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $domain
     * @param string $languageCode
     * @return Translation
     */
    protected function getTranslation($key, $value, $domain, $languageCode)
    {
        $translation = new Translation();
        $translation->setValue($value)
            ->setLanguage($this->getLanguage($languageCode))
            ->setTranslationKey($this->getTranslationKey($key, $domain));

        return $translation;
    }

    /**
     * @param string $code
     * @return Language
     */
    protected function getLanguage($code)
    {
        $language = new Language();
        $language->setCode($code);

        return $language;
    }

    /**
     * @param string $key
     * @param string $domain
     * @return TranslationKey
     */
    protected function getTranslationKey($key, $domain)
    {
        $translationKey = new TranslationKey();
        $translationKey->setKey($key)->setDomain($domain);

        return $translationKey;
    }
}
