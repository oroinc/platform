<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\EventListener\Fixtures;

class ObjectWithNestedAssociation
{
    /** @var string */
    public $relatedObjectClassName;

    /** @var integer */
    public $relatedObjectId;
}
