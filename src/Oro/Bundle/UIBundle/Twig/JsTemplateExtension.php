<?php

namespace Oro\Bundle\UIBundle\Twig;

class JsTemplateExtension extends \Twig_Extension
{
    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            'oro_js_template_content' => new \Twig_Filter_Method($this, 'prepareJsTemplateContent'),
        ];
    }

    /**
     * Prepares the given string to use inside JavaScript template.
     * Example:
     * <script type="text/html" id="my_template">
     *     content|oro_js_template_content|raw
     * </script>
     *
     * @param string $content
     *
     * @return string
     */
    public function prepareJsTemplateContent($content)
    {
        if (!$content) {
            return $content;
        }

        $result = '';
        $offset = 0;
        while (false !== $start = strpos($content, '<script', $offset)) {
            if (false !== $end = strpos($content, '</script>', $start + 7)) {
                $result .= substr($content, $offset, $start - $offset);
                $result .= '<% print("<sc" + "ript") %>';
                $result .= strtr(
                    substr($content, $start + 7, $end - $start - 7),
                    [
                        '<%' => '<% print("<" + "%") %>',
                        '%>' => '<% print("%" + ">") %>'
                    ]
                );
                $result .= '<% print("</sc" + "ript>") %>';
                $offset = $end + 9;
            }
        }
        $result .= substr($content, $offset, strlen($content) - $offset);

        return $result;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'oro_ui.js_template';
    }
}
