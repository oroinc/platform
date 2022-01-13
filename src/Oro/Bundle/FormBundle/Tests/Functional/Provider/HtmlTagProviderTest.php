<?php

namespace Oro\Bundle\FormBundle\Tests\Functional\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HtmlTagProviderTest extends WebTestCase
{
    /**
     * List of allowed element.
     *
     * @url http://www.tinymce.com/wiki.php/Configuration:valid_elements
     */
    private array $allowedElements = [
        '@[id|style|class]',
        'iframe[allowfullscreen|frameborder|height|marginheight|marginwidth|name|scrolling|src|width|allow]',
        'table[cellspacing|cellpadding|border|align|width]',
        'thead[align|valign]',
        'tbody[align|valign]',
        'tr[align|valign]',
        'td[align|valign|rowspan|colspan|bgcolor|nowrap|width|height]',
        'th[align|valign|rowspan|colspan|bgcolor|nowrap|width|height]',
        'a[!href|target|title|data-action]',
        'dl',
        'dt',
        'div[data-title|data-type]',
        'ul[type]',
        'ol[type]',
        'li',
        'em',
        'strong',
        'b',
        'p',
        'u',
        'font[color]',
        'i',
        'br',
        'span[data-title|data-type]',
        'img[src|width|height|alt]',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hgroup',
        'abbr',
        'address',
        'article',
        'audio',
        'bdo',
        'blockquote[cite]',
        'caption',
        'cite',
        'code',
        'col',
        'colgroup',
        'dd',
        'del',
        'details',
        'dfn',
        'figure',
        'figcaption',
        'picture',
        'footer',
        'header',
        'hr',
        'ins',
        'kbd',
        'mark',
        'menu',
        'nav',
        'pre',
        'q',
        'samp',
        'section',
        'small',
        'strike',
        'source[srcset|type|media|sizes]',
        'sub',
        'sup',
        'time',
        'tfoot',
        'var',
        'video[allowfullscreen|autoplay|loop|poster|src|controls]',
        'aside',
    ];

    private string $allowedTags = '<iframe></iframe><table></table><thead></thead><tbody></tbody><tr></tr><td></td>' .
    '<th></th><a></a><dl></dl><dt></dt><div></div><ul></ul><ol></ol><li></li><em></em><strong></strong><b></b><p></p>' .
    '<u></u><font></font><i></i><br><span></span><img><h1></h1><h2></h2><h3></h3><h4></h4><h5></h5><h6></h6>' .
    '<hgroup></hgroup><abbr></abbr><address></address><article></article><audio></audio><bdo></bdo>' .
    '<blockquote></blockquote><caption></caption><cite></cite><code></code><col></col><colgroup></colgroup>' .
    '<dd></dd><del></del><details></details><dfn></dfn><figure></figure><figcaption></figcaption>' .
    '<picture></picture><footer></footer><header></header><hr></hr><ins></ins><kbd></kbd><mark></mark>' .
    '<menu></menu><nav></nav><pre></pre><q></q><samp></samp><section></section><small></small><strike></strike>' .
    '<source></source><sub></sub><sup></sup><time></time><tfoot></tfoot><var></var><video></video>' .
    '<aside></aside>';

    /** @var HtmlTagProvider */
    private $htmlTagProvider;

    protected function setUp(): void
    {
        $this->initClient();
        $this->htmlTagProvider = $this->getContainer()->get('oro_form.provider.html_tag_provider');
    }

    public function testGetAllowedElementsDefaultScope()
    {
        $this->assertEquals($this->allowedElements, $this->htmlTagProvider->getAllowedElements('default'));
    }

    public function testGetAllowedTagsDefaultScope()
    {
        $this->assertEquals($this->allowedTags, $this->htmlTagProvider->getAllowedTags('default'));
    }
}
