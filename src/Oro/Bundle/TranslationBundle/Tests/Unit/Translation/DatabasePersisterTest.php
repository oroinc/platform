<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatabasePersister */
    protected $persister;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
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
            ->with($this->equalTo(Translation::class))
            ->will($this->returnValue($this->em));

        $this->persister = new DatabasePersister(
            $this->registry,
            $this->translationManager
        );

        // set batch size to 2
        $reflection = new \ReflectionProperty(get_class($this->persister), 'batchSize');
        $reflection->setAccessible(true);
        $reflection->setValue($this->persister, 2);
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

        $this->translationManager->expects($this->exactly(5))->method('saveValue')->willReturn(new Translation());
        $this->translationManager->expects($this->exactly(3))->method('flush');
        $this->translationManager->expects($this->exactly(3))->method('clear');

        $this->translationManager->expects($this->once())->method('invalidateCache')->with($this->testLocale);

        $this->persister->persist($this->testLocale, $this->testData);
    }

    public function testExceptionScenario()
    {
        $exceptionClass = '\LogicException';
        $this->setExpectedException($exceptionClass);
        $exception = new $exceptionClass();

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->once())->method('commit')->will($this->throwException($exception));
        $this->em->expects($this->once())->method('rollback');

        $this->translationManager->expects($this->exactly(5))->method('saveValue')->willReturn(new Translation());
        $this->translationManager->expects($this->exactly(3))->method('flush');
        $this->translationManager->expects($this->exactly(3))->method('clear');

        $this->translationManager->expects($this->never())->method('invalidateCache');

        $this->persister->persist($this->testLocale, $this->testData);
    }
}
