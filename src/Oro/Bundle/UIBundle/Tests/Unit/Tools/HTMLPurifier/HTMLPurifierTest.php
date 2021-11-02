<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Tools\HTMLPurifier;

use Oro\Bundle\UIBundle\Tools\HTMLPurifier\Error;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\ErrorCollector;
use Oro\Bundle\UIBundle\Tools\HTMLPurifier\HTMLPurifier;
use Oro\Component\Testing\TempDirExtension;

class HTMLPurifierTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var HTMLPurifier */
    private $purifier;

    protected function setUp(): void
    {
        $config = \HTMLPurifier_Config::create(\HTMLPurifier_HTML5Config::createDefault());
        $config->set('Core.CollectErrors', true);
        $config->set('Cache.SerializerPath', $this->getTempDir('cache_test_data'));
        $config->set('HTML.AllowedElements', ['div']);

        $this->purifier = new HTMLPurifier($config);
    }

    public function testPurify(): void
    {
        $htmlValue = '<div><div><h1>Hello World!</h1></div>';

        $resultHtml = $this->purifier->purify($htmlValue);

        $this->assertEquals('<div><div>Hello World!</div></div>', $resultHtml);

        /** @var ErrorCollector $errorCollector */
        $errorCollector = $this->purifier->context->get('ErrorCollector');
        $this->assertNotNull($errorCollector);
        $expectedErrors = [
            new Error('<div> tag started on line 1 closed by end of document', '<div><div><h1>Hello World'),
            new Error('Unrecognized <h1> tag removed', '<h1>Hello World!</h1></di'),
            new Error('Unrecognized </h1> tag removed', '</h1></div>'),
        ];
        $this->assertEquals($expectedErrors, $errorCollector->getErrorsList($htmlValue));
    }
}
