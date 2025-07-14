<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemIdTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TreeItemIdTransformerTest extends TestCase
{
    public function testTransformFailsOnUnsupportedType(): void
    {
        $this->expectException(TransformationFailedException::class);

        $transformer = new TreeItemIdTransformer([]);
        $transformer->transform((object)[]);
    }
}
