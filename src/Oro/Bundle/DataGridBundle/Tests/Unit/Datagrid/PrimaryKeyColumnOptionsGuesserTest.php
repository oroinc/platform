<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Datagrid\DefaultColumnOptionsGuesser;
use Oro\Bundle\DataGridBundle\Datagrid\Guess\ColumnGuess;
use Oro\Bundle\DataGridBundle\Datagrid\PrimaryKeyColumnOptionsGuesser;

class PrimaryKeyColumnOptionsGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var DefaultColumnOptionsGuesser */
    protected $guesser;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->guesser = new PrimaryKeyColumnOptionsGuesser($this->registry);
    }

    /**
     * @dataProvider guessFormatterProvider
     */
    public function testGuessFormatter(?array $identifier, ?ColumnGuess $expected): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn($identifier);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getClassMetadata')
            ->with('TestClass')
            ->willReturn($classMetadata);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('TestClass')
            ->willReturn($manager);

        $this->assertEquals($expected, $this->guesser->guessFormatter('TestClass', 'testProp', 'testType'));
    }

    public function guessFormatterProvider(): array
    {
        return [
            'testProp is primary key' => [
                'identifier' => ['testProp'],
                'expected' => new ColumnGuess(['frontend_type' => 'string'], ColumnGuess::MEDIUM_CONFIDENCE),
            ],

            'testProp not primary key' => [
                'identifier' => ['id'],
                'expected' => null,
            ],

            'testProp in part of composite primary key' => [
                'identifier' => ['propA', 'testProp', 'propC'],
                'expected' => new ColumnGuess(['frontend_type' => 'string'], ColumnGuess::MEDIUM_CONFIDENCE),
            ],

            'testProp not in part of composite primary key' => [
                'identifier' => ['propA', 'propB', 'propC'],
                'expected' => null,
            ],
        ];
    }
}
