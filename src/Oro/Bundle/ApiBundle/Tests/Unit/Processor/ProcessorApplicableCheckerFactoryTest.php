<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Processor\ProcessorApplicableCheckerFactory;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\Testing\ReflectionUtil;

class ProcessorApplicableCheckerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateApplicableChecker()
    {
        $factory = new ProcessorApplicableCheckerFactory();
        $chainApplicableChecker = $factory->createApplicableChecker();
        self::assertInstanceOf(ChainApplicableChecker::class, $chainApplicableChecker);
        $applicableCheckers = iterator_to_array($chainApplicableChecker);
        self::assertCount(1, $applicableCheckers);
        self::assertInstanceOf(MatchApplicableChecker::class, $applicableCheckers[0]);
        self::assertEquals([], ReflectionUtil::getPropertyValue($applicableCheckers[0], 'ignoredAttributes'));
        self::assertEquals(
            ['class' => true, 'parentClass' => true],
            ReflectionUtil::getPropertyValue($applicableCheckers[0], 'classAttributes')
        );
    }
}
