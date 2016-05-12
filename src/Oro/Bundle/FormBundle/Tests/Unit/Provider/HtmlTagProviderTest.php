<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HtmlTagProvider
     */
    protected $htmlTagProvider;

    /**
     * List of allowed element.
     *
     * @url http://www.tinymce.com/wiki.php/Configuration:valid_elements
     * @var array
     */
    protected $allowedElements = [
        '@[style|class]',
        'table[cellspacing|cellpadding|border|align|width]',
        'thead[align|valign]',
        'tbody[align|valign]',
        'tr[align|valign]',
        'td[align|valign|rowspan|colspan|bgcolor|nowrap|width|height]',
        'a[!href|target|title]',
        'dl',
        'dt',
        'div',
        'ul',
        'ol',
        'li',
        'em',
        'strong',
        'b',
        'p',
        'font[color]',
        'i',
        'br',
        'span',
        'img[src|width|height|alt]',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    protected $allowedTags = '';

    protected function setUp()
    {
        $this->htmlTagProvider = new HtmlTagProvider();

        $this->allowedTags = '<table></table><thead></thead><tbody></tbody><tr></tr><td></td><a></a><dl></dl>' .
            '<dt></dt><div></div><ul></ul><ol></ol><li></li><em></em><strong></strong><b></b><p></p><font></font>' .
            '<i></i><br><span></span><img><h1></h1><h2></h2><h3></h3><h4></h4><h5></h5><h6></h6>';
    }

    public function testGetAllowedElements()
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements();
        $this->assertEquals($this->allowedElements, $allowedElements);
    }

    public function testGetAllowedTags()
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags();
        $this->assertEquals($this->allowedTags, $allowedTags);
    }
}
