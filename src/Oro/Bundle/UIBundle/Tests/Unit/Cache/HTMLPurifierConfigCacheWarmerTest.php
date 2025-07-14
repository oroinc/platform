<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Cache;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\UIBundle\Cache\HTMLPurifierConfigCacheWarmer;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\TestCase;

class HTMLPurifierConfigCacheWarmerTest extends TestCase
{
    public function testWarmUp(): void
    {
        $htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);

        $htmlTagHelper->expects(self::exactly(2))
            ->method('sanitize')
            ->willReturnMap([
                ['<p style="border: none">text</p><a href="http://localhost"></a>', 'default', false, 'string'],
                ['<p style="border: none">text</p><a href="http://localhost"></a>', 'second', false, 'string']
            ]);

        $htmlTagHelper->expects(self::once())
            ->method('escape')
            ->with('<p style="border: none">text</p><a href="http://localhost"></a>');

        $htmlTagProvider->expects(self::once())
            ->method('getScopes')
            ->willReturn(['default', 'second']);

        $warmer = new HTMLPurifierConfigCacheWarmer($htmlTagHelper, $htmlTagProvider);
        $warmer->warmUp('test');
    }
}
