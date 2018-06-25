<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatabasePersister */
    protected $persister;

    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var TranslationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationManager;

    /** @var array */
    protected $testData = [
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

    /** @var string */
    protected $testLocale = 'en';

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationManager = $this
            ->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $language = new Language();

        $connection = $this->createMock(Connection::class);
        $this->em->expects($this->any())->method('getConnection')->willReturn($connection);

        $entityRepository = $this->createMock(EntityRepository::class);
        $entityRepository->expects($this->any())->method('findOneBy')->willReturn($language);
        $this->em->expects($this->any())->method('getRepository')->willReturn($entityRepository);

        $this->persister = new DatabasePersister(
            $this->registry,
            $this->translationManager
        );
    }

    protected function tearDown()
    {
        unset(
            $this->em,
            $this->persister,
            $this->translationManager,
            $this->registry
        );
    }

    public function testPersist()
    {
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');

        $this->translationManager->expects($this->once())->method('invalidateCache')->with($this->testLocale);
        $this->translationManager->expects($this->once())->method('clear');

        $this->persister->persist($this->testLocale, $this->testData);
    }

    public function testExceptionScenario()
    {
        $exceptionClass = '\LogicException';
        $this->expectException($exceptionClass);
        $exception = new $exceptionClass();

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit')->will($this->throwException($exception));
        $this->em->expects($this->once())->method('rollback');

        $this->translationManager->expects($this->never())->method('invalidateCache');
        $this->translationManager->expects($this->never())->method('clear');

        $this->persister->persist($this->testLocale, $this->testData);
    }
}
