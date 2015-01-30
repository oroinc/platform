<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

/**
 * @outputBuffering enabled
 */
class TwigRenderingTest extends LayoutTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testHtmlRenderingForCoreBlocks()
    {
        /** @var LayoutManager $layoutManager */
        $layoutManager = $this->client->getContainer()->get('oro_layout.layout_manager');

        $layout = $layoutManager->getLayoutBuilder()
            ->add('root', null, 'root')
            ->add('head', 'root', 'head', ['title' => 'Test'])
            ->add('meta', 'head', 'meta', ['charset' => 'UTF-8'])
            ->add('style', 'head', 'style', ['content' => 'body { color: red; }'])
            ->add('script', 'head', 'script', ['content' => 'alert(\'test\');'])
            ->add('content', 'root', 'body')
            ->getLayout(new LayoutContext());

        $result = $layout->render();

        $expected = <<<HTML
<!DOCTYPE html>
<html>
    <head>
        <title>Test</title>
        <meta charset="UTF-8" />
        <style type="text/css">
            body { color: red; }
        </style>
        <script type="text/javascript">
            alert('test');
        </script>
    </head>
<body>
</body>
</html>
HTML;

        $this->assertHtmlEquals($expected, $result);
    }
}
