<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Annotation\Fixtures\Controller\Classes;

class ExtendedFromAbstractController extends AbstractController
{
    protected function getResponse()
    {
        return '';
    }
}
