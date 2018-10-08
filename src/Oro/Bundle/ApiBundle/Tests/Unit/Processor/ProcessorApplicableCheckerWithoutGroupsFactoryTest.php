<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\MatchApplicableChecker;
use Oro\Bundle\ApiBundle\Processor\ProcessorApplicableCheckerWithoutGroupsFactory;
use Oro\Component\ChainProcessor\ChainApplicableChecker;

class ProcessorApplicableCheckerWithoutGroupsFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateApplicableChecker()
    {
        $factory = new ProcessorApplicableCheckerWithoutGroupsFactory();
        $chainApplicableChecker = $factory->createApplicableChecker();
        self::assertInstanceOf(ChainApplicableChecker::class, $chainApplicableChecker);
        $applicableCheckers = iterator_to_array($chainApplicableChecker);
        self::assertCount(1, $applicableCheckers);
        self::assertInstanceOf(MatchApplicableChecker::class, $applicableCheckers[0]);
        self::assertAttributeEquals([], 'ignoredAttributes', $applicableCheckers[0]);
        self::assertAttributeEquals(
            ['class' => true, 'parentClass' => true],
            'classAttributes',
            $applicableCheckers[0]
        );
    }
}
