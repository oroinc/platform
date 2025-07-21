<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Helper;

use Oro\Bundle\ScopeBundle\Helper\ContextRequestHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ContextRequestHelperTest extends TestCase
{
    public function testGetFromRequest(): void
    {
        $request = new Request();
        $context = ['user' => 5, 'organization' => 23];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }

    public function testGetFromRequestWithExtraKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context must contain only allowed keys: user, organization');

        $request = new Request();
        $context = ['user' => 5, 'organization' => 23, 'product' => 3];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }

    public function testGetFromRequestWithNotEnoughKeys(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Context must contain only allowed keys: user, organization');

        $request = new Request();
        $context = ['user' => 5];
        $request->query->set('context', $context);
        $keys = ['user', 'organization'];

        $helper = new ContextRequestHelper();
        $this->assertEquals($context, $helper->getFromRequest($request, $keys));
    }
}
