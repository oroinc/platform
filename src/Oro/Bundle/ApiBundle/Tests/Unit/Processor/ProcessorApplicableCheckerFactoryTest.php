<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Processor\ProcessorApplicableCheckerFactory;

class ProcessorApplicableCheckerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateApplicableChecker()
    {
        $factory = new ProcessorApplicableCheckerFactory();
        $chainApplicableChecker = $factory->createApplicableChecker();
        self::assertInstanceOf(ChainApplicableChecker::class, $chainApplicableChecker);
        $applicableCheckers = iterator_to_array($chainApplicableChecker);
        self::assertCount(1, $applicableCheckers);
        self::assertInstanceOf(MatchApplicableChecker::class, $applicableCheckers[0]);
        self::assertAttributeEquals(['group'], 'ignoredAttributes', $applicableCheckers[0]);
        self::assertAttributeEquals(
            ['class' => true, 'parentClass' => true],
            'classAttributes',
            $applicableCheckers[0]
        );
    }
}
