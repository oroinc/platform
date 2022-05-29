<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatabasePersister */
    private $persister;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $translationManager;

    private array $testData = [
        'messages'   => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
            'key_3' => 'value_3',
        ],
        'validators' => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
        ]
    ];

    private string $testLocale = 'en';

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->translationManager = $this->createMock(TranslationManager::class);

        $language = new Language();

        $connection = $this->createMock(Connection::class);
        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($language);
        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->persister = new DatabasePersister($doctrine, $this->translationManager);
    }

    public function testPersist()
    {
        $this->em->expects($this->once())
            ->method('beginTransaction');
        $this->em->expects($this->once())
            ->method('commit');
        $this->em->expects($this->never())
            ->method('rollback');

        $this->translationManager->expects($this->once())
            ->method('invalidateCache')
            ->with($this->testLocale);
        $this->translationManager->expects($this->once())
            ->method('clear');

        $this->persister->persist($this->testLocale, $this->testData);
    }

    public function testExceptionScenario()
    {
        $this->expectException(\LogicException::class);

        $this->em->expects($this->once())
            ->method('beginTransaction');
        $this->em->expects($this->once())
            ->method('commit')
            ->willThrowException(new \LogicException());
        $this->em->expects($this->once())
            ->method('rollback');

        $this->translationManager->expects($this->never())
            ->method('invalidateCache');
        $this->translationManager->expects($this->never())
            ->method('clear');

        $this->persister->persist($this->testLocale, $this->testData);
    }
}
