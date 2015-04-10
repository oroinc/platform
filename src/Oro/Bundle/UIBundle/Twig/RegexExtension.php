<?php

namespace Oro\Bundle\UIBundle\Twig;

class RegexExtension extends \Twig_Extension
{
    const NAME = 'oro_regex';

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_preg_replace', [$this, 'pregReplace'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param string $subject
     * @param string $pattern
     * @param string $replacement
     * @param int $limit
     *
     * @return mixed
     */
    public function pregReplace($subject, $pattern, $replacement, $limit = -1)
    {
        if (is_string($subject) && strlen($subject)) {
            $subject = preg_replace($pattern, $replacement, $subject, $limit);
        }

        return $subject;
    }
}
