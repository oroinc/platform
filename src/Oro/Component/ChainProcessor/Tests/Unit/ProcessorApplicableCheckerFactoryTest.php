<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\GroupRangeApplicableChecker;
use Oro\Component\ChainProcessor\MatchApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactory;
use Oro\Component\ChainProcessor\SkipGroupApplicableChecker;

class ProcessorApplicableCheckerFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateApplicableChecker()
    {
        $factory = new ProcessorApplicableCheckerFactory();
        $chainApplicableChecker = $factory->createApplicableChecker();
        self::assertInstanceOf(ChainApplicableChecker::class, $chainApplicableChecker);
        $applicableCheckers = iterator_to_array($chainApplicableChecker);
        self::assertCount(3, $applicableCheckers);
        self::assertInstanceOf(MatchApplicableChecker::class, $applicableCheckers[0]);
        self::assertInstanceOf(SkipGroupApplicableChecker::class, $applicableCheckers[1]);
        self::assertInstanceOf(GroupRangeApplicableChecker::class, $applicableCheckers[2]);
    }
}
