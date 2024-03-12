<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes;

class ExtendedFromAbstractController extends AbstractController
{
    protected function getResponse()
    {
        return '';
    }
}
