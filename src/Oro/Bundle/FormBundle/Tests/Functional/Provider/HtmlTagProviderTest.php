<?php

namespace Oro\Bundle\FormBundle\Tests\Functional\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HtmlTagProviderTest extends WebTestCase
{
    /** @var HtmlTagProvider */
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
        'div[id]',
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
        'span[id]',
        'img[src|width|height|alt]',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    /** @var string */
    protected $allowedTags = '';

    protected function setUp()
    {
        $this->initClient();
        $this->htmlTagProvider = $this->getContainer()->get('oro_form.provider.html_tag_provider');

        $this->allowedTags = '<table></table><thead></thead><tbody></tbody><tr></tr><td></td><a></a><dl></dl>' .
            '<dt></dt><div></div><ul></ul><ol></ol><li></li><em></em><strong></strong><b></b><p></p><font></font>' .
            '<i></i><br><span></span><img><h1></h1><h2></h2><h3></h3><h4></h4><h5></h5><h6></h6>';
    }

    public function testGetAllowedElements()
    {
        $this->assertEquals($this->allowedElements, $this->htmlTagProvider->getAllowedElements());
    }

    public function testGetAllowedTags()
    {
        $this->assertEquals($this->allowedTags, $this->htmlTagProvider->getAllowedTags());
    }
}
