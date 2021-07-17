<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Utils;

use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizer;

class PinbarTabUrlNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider urlsDataProvider
     */
    public function testGetNormalizedUrl(string $url, string $expectedUrl): void
    {
        $normalizer = new PinbarTabUrlNormalizer();

        self::assertEquals($expectedUrl, $normalizer->getNormalizedUrl($url));
    }

    public function urlsDataProvider(): array
    {
        return [
            'empty URL' => [
                'url' => '',
                'expectedUrl' => '',
            ],
            'empty path' => [
                'url' => '/',
                'expectedUrl' => '/',
            ],
            'no GET parameters, no fragment' => [
                'url' => '/sample-url',
                'expectedUrl' => '/sample-url',
            ],
            'no GET parameters, with fragment' => [
                'url' => '/sample-url#sample-fragment',
                'expectedUrl' => '/sample-url#sample-fragment',
            ],
            'with GET parameters, no fragment' => [
                'url' => '/sample-url?b=1&a=2',
                'expectedUrl' => '/sample-url?a=2&b=1',
            ],
            'with GET parameters, with fragment' => [
                'url' => '/sample-url?b=1&a=2#sample-fragment',
                'expectedUrl' => '/sample-url?a=2&b=1#sample-fragment',
            ],
            'with datagrid parameters' => [
                'url' => '/sample-url?grid%5Busers-grid%5D=i%3D1%26p%3D25%26s%255Busername%255D%3D-1%26f%255Benabled'
                    .'%255D%255Bvalue%255D%3D1%26c%3DfirstName1.lastName1.email1.username1.enabled0.auth_status1.'
                    .'createdAt1.updatedAt1.tags1%26v%3Duser.active%26a%3Dgrid%26g%255BoriginalRoute%255D%3D'
                    .'oro_user_index&b=extra-parameter',
                'expectedUrl' => '/sample-url?b=extra-parameter&grid%5Busers-grid%5D=a%3Dgrid%26c%3DfirstName1.'
                    .'lastName1.email1.username1.enabled0.auth_status1.createdAt1.updatedAt1.tags1%26f%255Benabled'
                    .'%255D%255Bvalue%255D%3D1%26g%255BoriginalRoute%255D%3Doro_user_index%26i%3D1%26p%3D25%26s%255B'.
                    'username%255D%3D-1%26v%3Duser.active',
            ],
            'with invalid datagrid parameters' => [
                'url' => '/sample-url?grid%5Busers-grid%5D%5Bfoo%5D=bar&grid%5Busers-grid%5D%5Bbar%5D=foo',
                'expectedUrl' => '/sample-url?grid%5Busers-grid%5D%5Bbar%5D=foo&grid%5Busers-grid%5D%5Bfoo%5D=bar',
            ],
        ];
    }
}
