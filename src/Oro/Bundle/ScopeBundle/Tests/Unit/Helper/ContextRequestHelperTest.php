<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Helper;

use Oro\Bundle\ScopeBundle\Helper\ContextRequestHelper;
use Symfony\Component\HttpFoundation\Request;

class ContextRequestHelperTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context must contain only allowed keys: user, organization
     */
    public function testGetFromRequestWithExtraKeys()
    {
        $request = new Request();
        $context = ['user' => 5, 'organization' => 23, 'product' => 3];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Context must contain only allowed keys: user, organization
     */
    public function testGetFromRequestWithNotEnoughKeys()
    {
        $request = new Request();
        $context = ['user' => 5];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }
}
