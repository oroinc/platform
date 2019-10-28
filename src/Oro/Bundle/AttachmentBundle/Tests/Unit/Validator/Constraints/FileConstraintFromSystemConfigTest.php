<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AttachmentBundle\Validator\Constraints\FileConstraintFromSystemConfig;
use Symfony\Component\Validator\Constraint;

class FileConstraintFromSystemConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTargets(): void
    {
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, (new FileConstraintFromSystemConfig())->getTargets());
    }
}
