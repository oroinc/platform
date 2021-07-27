<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemIdTransformer;

class TreeItemIdTransformerTest extends \PHPUnit\Framework\TestCase
{
    public function testTransformFailsOnUnsupportedType()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $transformer = new TreeItemIdTransformer([]);
        $transformer->transform((object)[]);
    }
}
