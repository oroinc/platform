<?php

namespace Oro\Bundle\DigitalAssetBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\File;

/**
 * Constraint for checking mime type and file size of the uploaded sourceFile of DigitalAsset entity
 */
class DigitalAssetSourceFile extends File
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
