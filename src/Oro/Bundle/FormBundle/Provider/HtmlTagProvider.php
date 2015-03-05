<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Class HtmlTagProvider
 *
 * @package Oro\Bundle\FormBundle\Form\Provider
 */
class HtmlTagProvider
{
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
        'a[!href|target=_blank|title]',
        'dl',
        'dt',
        'div',
        'ul',
        'ol',
        'li',
        'em',
        'strong/b',
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

    public function getAllowedElements()
    {
        return $this->allowedElements;
    }

    /**
     * todo refactor
     *
     * @return string
     */
    public function getAllowedTags()
    {
        return '<table></table><thead></thead><tbody></tbody><tr></tr><td></td><a></a><dl></dl><dt></dt><div></div>' .
            '<ul></ul><ol></ol><li></li><em></em><strong></strong><b></b><p></p><i></i><br><br/><span></span>' .
            '<img><h1></h1><h2></h2><h3></h3><h4></h4><h5></h5><h6></h6>';
    }
}
