<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\UIBundle\Form\DataTransformer\TreeItemIdTransformer;

class TreeItemIdTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     */
    public function testTransformFailsOnUnsupportedType()
    {
        $transformer = new TreeItemIdTransformer([]);
        $transformer->transform((object)[]);
    }
}
