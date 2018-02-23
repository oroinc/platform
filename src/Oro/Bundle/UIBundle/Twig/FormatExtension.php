<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FormatExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * @return FormatterManager
     */
    protected function getFormatterManager()
    {
        return $this->container->get('oro_ui.formatter');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_format', [$this, 'format'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('age', [$this, 'getAge']),
            new \Twig_SimpleFilter('age_string', [$this, 'getAgeAsString']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('asset_path', [$this, 'generateUrlWithoutFrontController']),
            new \Twig_SimpleFunction('oro_format_filename', [$this, 'formatFilename']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_formatter_extension';
    }

    /**
     * @param mixed  $parameter
     * @param string $formatterName
     * @param array  $formatterArguments
     *
     * @return mixed
     */
    public function format($parameter, $formatterName, array $formatterArguments = [])
    {
        return $this->getFormatterManager()->format($parameter, $formatterName, $formatterArguments);
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return string
     */
    public function generateUrlWithoutFrontController($name, $parameters = [])
    {
        $router = $this->container->get('router');

        $prevBaseUrl = $router->getContext()->getBaseUrl();
        $baseUrlWithoutFrontController = preg_replace('/\/[\w_]+\.php$/', '', $prevBaseUrl);
        $router->getContext()->setBaseUrl($baseUrlWithoutFrontController);

        $url = $router->generate($name, $parameters);

        $router->getContext()->setBaseUrl($prevBaseUrl);

        return $url;
    }

    /**
     * @param string $filename
     * @param int    $cutLength
     * @param int    $start
     * @param int    $end
     *
     * @return string
     */
    public function formatFilename($filename, $cutLength = 15, $start = 7, $end = 7)
    {
        $encoding = mb_detect_encoding($filename);

        if (mb_strlen($filename, $encoding) > $cutLength) {
            $filename = mb_substr($filename, 0, $start, $encoding)
                . '..'
                . mb_substr($filename, mb_strlen($filename, $encoding) - $end, null, $encoding);
        }

        return $filename;
    }

    /**
     * Get age as number of years.
     *
     * @param string|\DateTime $date
     * @param array            $options
     *
     * @return int
     */
    public function getAge($date, $options)
    {
        if (!$date) {
            return null;
        }
        $dateDiff = $this->getDateDiff($date, $options);
        if ($dateDiff->invert) {
            return null;
        } else {
            return $dateDiff->y;
        }
    }

    /**
     * Get translated age string.
     *
     * @param string|\DateTime $date
     * @param array            $options
     *
     * @return string
     */
    public function getAgeAsString($date, $options)
    {
        if (!$date) {
            return '';
        }
        $dateDiff = $this->getDateDiff($date, $options);
        if ($dateDiff->invert) {
            return isset($options['default']) ? $options['default'] : '';
        }

        $age = $dateDiff->y;

        return $this->getTranslator()->transChoice('oro.age', $age, ['%count%' => $age], 'messages');
    }

    /**
     * @param mixed $date
     * @param array $options
     *
     * @return \DateInterval|null
     */
    protected function getDateDiff($date, $options)
    {
        if (!$date) {
            return null;
        }
        if (!$date instanceof \DateTime) {
            $format = isset($options['format']) ? $options['format'] : 'Y-m-d';
            $tz = isset($options['timezone'])
                ? new \DateTimeZone($options['timezone'])
                : new \DateTimeZone('UTC');
            $date = \DateTime::createFromFormat($format, $date, $tz);
        }

        return $date->diff(new \DateTime('now'));
    }
}
