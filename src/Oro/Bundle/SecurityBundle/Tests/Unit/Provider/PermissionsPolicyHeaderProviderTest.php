<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Provider;

use Oro\Bundle\SecurityBundle\Provider\PermissionsPolicyHeaderProvider;
use PHPUnit\Framework\TestCase;

class PermissionsPolicyHeaderProviderTest extends TestCase
{
    public function testProvider()
    {
        $provider = new PermissionsPolicyHeaderProvider(
            true,
            [
                'test1' => ['allow_self'],
                'test2' => ['deny'],
                'test3' => ['allow_all'],
                'test4' => ['http://test.com', 'allow_self']
            ]
        );

        $this->assertTrue($provider->isEnabled());
        $this->assertEquals(
            'test1=(self), test2=(), test3=*, test4=("http://test.com" self)',
            $provider->getDirectivesValue()
        );
    }
}
