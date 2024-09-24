<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Attribute\Fixtures\Controller\Classes;

class ExtendedFromAbstractController extends AbstractController
{
    #[\Override]
    protected function getResponse()
    {
        return '';
    }
}
