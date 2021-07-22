<?php

namespace Oro\Bundle\UIBundle\Twig;

use Oro\Bundle\UIBundle\Formatter\FormatterManager;
use Oro\Bundle\UIBundle\Provider\UrlWithoutFrontControllerProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to render files:
 *   - asset_path
 *   - oro_format_filename
 *
 * Provides Twig filters for content formatting:
 *   - oro_format
 *   - age
 *   - age_string
 */
class FormatExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get(TranslatorInterface::class);
    }

    /**
     * @return FormatterManager
     */
    protected function getFormatterManager()
    {
        return $this->container->get('oro_ui.formatter');
    }

    /**
     * @return UrlWithoutFrontControllerProvider
     */
    protected function getUrlWithoutFrontControllerProvider()
    {
        return $this->container->get('oro_ui.provider.url_without_front_controller');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('oro_format', [$this, 'format'], ['is_safe' => ['html']]),
            new TwigFilter('age', [$this, 'getAge']),
            new TwigFilter('age_string', [$this, 'getAgeAsString']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('asset_path', [$this, 'generateUrlWithoutFrontController']),
            new TwigFunction('oro_format_filename', [$this, 'formatFilename']),
        ];
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
        return $this->getUrlWithoutFrontControllerProvider()->generate($name, $parameters);
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
        }

        return $dateDiff->y;
    }

    /**
     * Get translated age string.
     *
     * @param string|\DateTime $date
     * @param array            $options
     *
     * @return string
     */
    public function getAgeAsString($date, $options = [])
    {
        if (!$date) {
            return '';
        }
        $dateDiff = $this->getDateDiff($date, $options);
        if ($dateDiff->invert) {
            return $options['default'] ?? '';
        }

        $age = $dateDiff->y;

        return $this->getTranslator()->trans('oro.age', ['%count%' => $age], 'messages');
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
            $format = $options['format'] ?? 'Y-m-d';
            $tz = isset($options['timezone'])
                ? new \DateTimeZone($options['timezone'])
                : new \DateTimeZone('UTC');
            $date = \DateTime::createFromFormat($format, $date, $tz);
        }

        return $date->diff(new \DateTime('now'));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            TranslatorInterface::class,
            'oro_ui.formatter' => FormatterManager::class,
            'oro_ui.provider.url_without_front_controller' => UrlWithoutFrontControllerProvider::class,
        ];
    }
}
