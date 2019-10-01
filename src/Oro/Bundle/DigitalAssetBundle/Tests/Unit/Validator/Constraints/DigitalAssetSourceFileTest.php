<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFile;
use Symfony\Component\Validator\Constraint;

class DigitalAssetSourceFileTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets(): void
    {
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, (new DigitalAssetSourceFile())->getTargets());
    }
}
