<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Processor\ProcessorApplicableCheckerFactory;
use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\MatchApplicableChecker as BaseMatchApplicableChecker;

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

        $ignoredAttributesProperty = new \ReflectionProperty(BaseMatchApplicableChecker::class, 'ignoredAttributes');
        $ignoredAttributesProperty->setAccessible(true);
        static::assertEquals([], $ignoredAttributesProperty->getValue($applicableCheckers[0]));

        $classAttributesProperty = new \ReflectionProperty(MatchApplicableChecker::class, 'classAttributes');
        $classAttributesProperty->setAccessible(true);
        static::assertEquals(
            ['class' => true, 'parentClass' => true],
            $classAttributesProperty->getValue($applicableCheckers[0])
        );
    }
}
