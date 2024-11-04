<?php

namespace Oro\Bundle\TranslationBundle\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base class for fixtures that load translatable entities.
 */
abstract class AbstractTranslatableEntityFixture extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const ENTITY_DOMAIN = 'entities';
    private const DOMAIN_FILE_REGEXP = '/^%domain%\.(.*?)\./';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translationDirectory = '/Resources/translations/';

    /**
     * @var array
     */
    protected $translationLocales;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->translator = $this->container->get('translator');
        $this->loadEntities($manager);
    }

    /**
     * Gets regexp for current entity domain.
     */
    protected function getDomainFileRegExp(): string
    {
        return str_replace('%domain%', self::ENTITY_DOMAIN, self::DOMAIN_FILE_REGEXP);
    }

    /**
     * Gets list of existing translation locales for current translation domain.
     */
    protected function getTranslationLocales(): array
    {
        if (null === $this->translationLocales) {
            $translationDirectory = str_replace('/', DIRECTORY_SEPARATOR, $this->translationDirectory);
            $translationDirectories = [];

            foreach ($this->container->getParameter('kernel.bundles') as $bundle) {
                $reflection = new \ReflectionClass($bundle);
                $bundleTranslationDirectory = \dirname($reflection->getFileName()) . $translationDirectory;
                if (is_dir($bundleTranslationDirectory) && is_readable($bundleTranslationDirectory)) {
                    $translationDirectories[] = realpath($bundleTranslationDirectory);
                }
            }

            $domainFileRegExp = $this->getDomainFileRegExp();

            $finder = new Finder();
            $finder->in($translationDirectories)->name($domainFileRegExp);

            $this->translationLocales = [];
            foreach ($finder as $file) {
                preg_match($domainFileRegExp, $file->getFilename(), $matches);
                if ($matches) {
                    $this->translationLocales[] = $matches[1];
                }
            }
            $this->translationLocales = array_unique($this->translationLocales);
        }

        return $this->translationLocales;
    }

    /**
     * Translates the given string.
     */
    protected function translate(
        string $id,
        ?string $prefix = null,
        ?string $locale = null,
        array $parameters = [],
        ?string $domain = null
    ): string {
        if (!$domain) {
            $domain = self::ENTITY_DOMAIN;
        }

        return $this->translator->trans($this->getTranslationId($id, $prefix), $parameters, $domain, $locale);
    }

    /**
     * Gets translation ID based on source ID and prefix.
     */
    protected function getTranslationId(string $id, ?string $prefix = null): string
    {
        return ($prefix ? $prefix . '.' : '') . $id;
    }

    abstract protected function loadEntities(ObjectManager $manager);
}
