<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Helper;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ScopeBundle\Helper\ContextRequestHelper;

class ContextRequestHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFromRequest()
    {
        $request = new Request();
        $context = ['user' => 5, 'organization' => 23];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }
}
