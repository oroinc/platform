<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\DigitalAssetBundle\Validator\Constraints\DigitalAssetSourceFileMimeType;
use Symfony\Component\Validator\Constraint;

class DigitalAssetSourceFileMimeTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var DigitalAssetSourceFileMimeType */
    private $constraint;

    protected function setUp(): void
    {
        $this->constraint = new DigitalAssetSourceFileMimeType();
    }

    public function testProperties(): void
    {
        $this->assertEquals(
            'oro.digitalasset.validator.mime_type_cannot_be_non_image_in_entity.message',
            $this->constraint->mimeTypeCannotBeNonImageInEntity
        );
        $this->assertEquals(
            'oro.digitalasset.validator.mime_type_cannot_be_non_image.message',
            $this->constraint->mimeTypeCannotBeNonImage
        );
    }

    public function testGetTargets(): void
    {
        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }
}
