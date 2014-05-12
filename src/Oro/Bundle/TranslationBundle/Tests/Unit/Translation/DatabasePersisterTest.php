<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatabasePersister */
    protected $persister;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataCache;

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

    public function setUp()
    {
        $this->em            = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();
        $this->repo          = $this->getMockBuilder(
            'Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository'
        )
            ->disableOriginalConstructor()->getMock();
        $this->metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()->getMock();


        $this->em->expects($this->any())->method('getRepository')->with($this->equalTo(Translation::ENTITY_NAME))
            ->will($this->returnValue($this->repo));
        $this->persister = new DatabasePersister($this->em, $this->metadataCache);

        // set batch size to 2
        $reflection = new \ReflectionProperty(get_class($this->persister), 'batchSize');
        $reflection->setAccessible(true);
        $reflection->setValue($this->persister, 2);
    }

    public function tearDown()
    {
        unset($this->em, $this->persister, $this->repo);
    }

    public function testPersist()
    {
        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(5))->method('persist');
        $this->em->expects($this->exactly(3))->method('flush');
        $this->em->expects($this->exactly(3))->method('clear');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');

        $this->metadataCache->expects($this->once())->method('updateTimestamp')->with($this->testLocale);

        $this->persister->persist($this->testLocale, $this->testData);
    }

    public function testPersistUpdateScenario()
    {
        $testValue         = 'some Value';
        $existsTranslation = new Translation();
        $existsTranslation->setValue($testValue);

        $this->repo->expects($this->any())->method('findValue')
            ->will(
                $this->returnValueMap(
                    [
                        ['key_1', $this->testLocale, 'messages', Translation::SCOPE_SYSTEM, null],
                        ['key_2', $this->testLocale, 'messages', Translation::SCOPE_SYSTEM, null],
                        ['key_3', $this->testLocale, 'messages', Translation::SCOPE_SYSTEM, null],
                        ['key_1', $this->testLocale, 'validators', Translation::SCOPE_SYSTEM, $existsTranslation],
                        ['key_2', $this->testLocale, 'validators', Translation::SCOPE_SYSTEM, null],
                    ]
                )
            );

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(5))->method('persist');
        $this->em->expects($this->exactly(3))->method('flush');
        $this->em->expects($this->exactly(3))->method('clear');
        $this->em->expects($this->once())->method('commit');
        $this->em->expects($this->never())->method('rollback');

        $this->metadataCache->expects($this->once())->method('updateTimestamp')->with($this->testLocale);

        $this->persister->persist($this->testLocale, $this->testData);

        $this->assertSame($this->testData['validators']['key_1'], $existsTranslation->getValue());
        $this->assertNotSame($testValue, $existsTranslation->getValue());
    }

    public function testExceptionScenario()
    {
        $exceptionClass = '\LogicException';
        $this->setExpectedException($exceptionClass);
        $exception = new $exceptionClass();

        $this->em->expects($this->once())->method('beginTransaction');
        $this->em->expects($this->exactly(5))->method('persist');
        $this->em->expects($this->exactly(3))->method('flush');
        $this->em->expects($this->exactly(3))->method('clear');
        $this->em->expects($this->once())->method('commit')->will($this->throwException($exception));
        $this->em->expects($this->once())->method('rollback');

        $this->metadataCache->expects($this->never())->method('updateTimestamp');

        $this->persister->persist($this->testLocale, $this->testData);
    }
}
