<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\JsTemplateExtension;

class JsTemplateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var JsTemplateExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new JsTemplateExtension();
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();
        $this->assertInternalType('array', $filters);
        $this->assertArrayHasKey('oro_js_template_content', $filters);
        $this->assertInstanceOf('\Twig_Filter_Method', $filters['oro_js_template_content']);
    }

    /**
     * @dataProvider prepareJsTemplateContentProvider
     */
    public function testPrepareJsTemplateContent($content, $expectedContent)
    {
        $result = $this->extension->prepareJsTemplateContent($content);
        $this->assertEquals($expectedContent, $result);
    }

    public function prepareJsTemplateContentProvider()
    {
        return [
            'null'                                  => [
                null,
                null,
            ],
            'empty'                                 => [
                '',
                '',
            ],
            'no script, no js template'             => [
                '<div>test</div>',
                '<div>test</div>',
            ],
            'no script, with js template'           => [
                '<div><%= test %></div>',
                '<div><%= test %></div>',
            ],
            'with script, no js template'           => [
                '<script type="text/javascript">var a = 1;</script>',
                '<% print("<sc" + "ript") %> type="text/javascript">var a = 1;<% print("</sc" + "ript>") %>',
            ],
            'js template inside script'             => [
                '<script type="text/javascript">var a = "<%= var %>";</script>',
                '<% print("<sc" + "ript") %> type="text/javascript">'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>',
            ],
            'js template inside and outside script' => [
                '<div><%= var %></div>' + "\n"
                . '<script type="text/javascript">var a = "<%= var %>";</script>' + "\n"
                . '<div><%= var %></div>' + "\n"
                . '<script>var a = "<%= var %>";</script>' + "\n"
                . 'some text',
                '<div><%= var %></div>' + "\n"
                . '<% print("<sc" + "ript") %> type="text/javascript">'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>' + "\n"
                . '<div><%= var %></div>' + "\n"
                . '<% print("<sc" + "ript") %>>'
                . 'var a = "<% print("<" + "%") %>= var <% print("%" + ">") %>";'
                . '<% print("</sc" + "ript>") %>' + "\n"
                . 'some text',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oro_ui.js_template', $this->extension->getName());
    }
}
