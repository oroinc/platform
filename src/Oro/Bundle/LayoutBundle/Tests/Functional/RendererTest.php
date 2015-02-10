<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional;

use Oro\Component\Layout\Layout;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\LayoutManager;

class RendererTest extends LayoutTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testHtmlRenderingForCoreBlocksByTwigRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.twig.resources')) {
            $this->markTestSkipped('TWIG renderer is not enabled.');
        }

        $result   = $this->getCoreBlocksTestLayout()->setRenderer('twig')->render();
        $expected = $this->getCoreBlocksTestLayoutResult();

        $this->assertHtmlEquals($expected, $result);
    }

    public function testHtmlRenderingForCoreBlocksByPhpRenderer()
    {
        if (!$this->getContainer()->hasParameter('oro_layout.php.resources')) {
            $this->markTestSkipped('PHP renderer is not enabled.');
        }

        $result   = $this->getCoreBlocksTestLayout()->setRenderer('php')->render();
        $expected = $this->getCoreBlocksTestLayoutResult();

        $this->assertHtmlEquals($expected, $result);
    }

    /**
     * @return Layout
     */
    protected function getCoreBlocksTestLayout()
    {
        /** @var LayoutManager $layoutManager */
        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');

        $layout = $layoutManager->getLayoutBuilder()
            ->add('root', null, 'root')
            ->add('head', 'root', 'head', ['title' => 'Test'])
            ->add('meta', 'head', 'meta', ['charset' => 'UTF-8'])
            ->add('style', 'head', 'style', ['content' => 'body { color: red; }'])
            ->add('script', 'head', 'script', ['content' => 'alert(\'test\');'])
            ->add('content', 'root', 'body')
            ->getLayout(new LayoutContext());

        return $layout;
    }

    /**
     * @return string
     */
    protected function getCoreBlocksTestLayoutResult()
    {
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

        return $expected;
    }
}
