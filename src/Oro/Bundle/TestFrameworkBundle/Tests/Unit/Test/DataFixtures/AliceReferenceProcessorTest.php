<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Test\DataFixtures;

use Nelmio\Alice\Instances\Processor\Processable;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceReferenceProcessor;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AliceReferenceProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getProcessedValues
     * @param bool   $expectedMatch
     * @param string $value
     */
    public function testCanProcess($expectedMatch, $value)
    {
        $processable = new Processable($value);
        /** @var RegistryInterface $registry */
        $registry = $this->createMock(RegistryInterface::class);
        $aliceReferenceProcessor = new AliceReferenceProcessor($registry);

        self::assertSame($expectedMatch, $aliceReferenceProcessor->canProcess($processable));
    }

    /**
     * @return array
     */
    public function getProcessedValues()
    {
        return [
            [true,  '@ref'],
            [true,  '@ref->id'],
            [true,  '@ref->owner->getId()'],
            [false, 'ref'],
            [false, '@ref*'],
            [false, '@ref<current()>'],
            [false, '<current()>'],
            [false, '<current()>@example.org'],
        ];
    }
}
