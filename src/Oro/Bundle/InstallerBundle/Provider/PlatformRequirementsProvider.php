<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Oro\Bundle\AttachmentBundle\Exception\ProcessorsException;
use Oro\Bundle\AttachmentBundle\Exception\ProcessorsVersionException;
use Oro\Bundle\AttachmentBundle\ProcessorHelper;
use Oro\Bundle\AttachmentBundle\ProcessorVersionChecker;
use Oro\Bundle\DistributionBundle\OroKernel;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Process\Process;
use Symfony\Requirements\RequirementCollection;

/**
 * Platform requirements provider
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PlatformRequirementsProvider extends AbstractRequirementsProvider
{
    public const REQUIRED_PHP_VERSION = OroKernel::REQUIRED_PHP_VERSION;

    public const REQUIRED_GD_VERSION = '2.0';

    public const REQUIRED_CURL_VERSION = '7.0';

    protected Connection $connection;
    protected string $projectDirectory;
    private ProcessorHelper $processorHelper;

    public function __construct(Connection $connection, string $projectDirectory, ProcessorHelper $processorHelper)
    {
        $this->connection = $connection;
        $this->projectDirectory = $projectDirectory;
        $this->processorHelper = $processorHelper;
    }

    /**
     * @inheritDoc
     */
    public function getMandatoryRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        $this->addTmpDirWritableRequirement($collection);
        $this->addVendorInstalledRequirement($collection);
        $this->addIconvExtInstalledRequirement($collection);
        $this->addPcntlExtInstalledRequirement($collection);
        $this->addJsonExtInstalledRequirement($collection);
        $this->addSessionExtInstalledRequirement($collection);
        $this->addCtypeExtInstalledRequirement($collection);
        $this->addTokenizerExtInstalledRequirement($collection);
        $this->addSimpleXmlExtInstalledRequirement($collection);
        $this->addPdoExtInstalledRequirement($collection);
        $this->addTimeZoneRequirement($collection);

        $this->addPathWritableRequirement($collection, 'var/cache');
        $this->addPathWritableRequirement($collection, 'var/logs');

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addFileNameLengthRequirement($collection);
        }
        if (function_exists('apc_store') && ini_get('apc.enabled')) {
            $this->addApcExtVersionRequirement($collection);
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getPhpIniRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        $this->addMemoryLimitIniRequirement($collection);
        $this->addDetectUnicodeIniRequirement($collection);

        if (extension_loaded('mbstring')) {
            $this->addMbstringOverloadDisabledIniRequirement($collection);
        }
        if (extension_loaded('suhosin')) {
            $this->addSuhosinWhitelistIniRequirement($collection);
        }
        if (extension_loaded('xdebug')) {
            $this->addXdebugShowExceptionTraceIniRequirement($collection);
            $this->addXdebugScreamIniRequirement($collection);
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getOroRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        $this->addPhpVersionRequirement($collection);
        $this->addGdVersionRequirement($collection);
        $this->addCurlVersionRequirement($collection);
        $this->addOpenSslExtRequirement($collection);
        $this->addIntlExtRequirement($collection);
        $this->addZipExtRequirement($collection);
        $this->addMbstringExtRequirement($collection);
        $this->addConfiguredConnectionRequirement($collection);

        $this->addImageProcessorsRequirement($collection, ProcessorHelper::JPEGOPTIM);
        $this->addImageProcessorsRequirement($collection, ProcessorHelper::PNGQUANT);

        $this->addPathWritableRequirement($collection, 'public/media');
        $this->addPathWritableRequirement($collection, 'public/bundles');
        $this->addPathWritableRequirement($collection, 'var/data');
        $this->addPathWritableRequirement($collection, 'public/js');

        if (function_exists('iconv')) {
            $this->addIconvBehaviorRequirement($collection);
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getRecommendations(): ?RequirementCollection
    {
        $collection = new RequirementCollection();
        $this->addIcuRecommendation($collection);
        $this->addSoapClientRecommendation($collection);
        $this->addTidyExtRecommendation($collection);
        $this->addPharExtRecommendation($collection);
        $this->addImapExtRecommendation($collection);
        $this->addDomDocumentExtRecommendation($collection);
        $this->addMbstringExtRecommendation($collection);
        $this->addXmlExtRecommendation($collection);
        $this->addFilterExtRecommendation($collection);
        $this->addPosixExtRecommendation($collection);
        $this->addAcceleratorExtRecommendation($collection);
        $this->addGdExtRecommendation($collection);

        $this->addImageProcessorRecommendation($collection, ProcessorHelper::JPEGOPTIM);
        $this->addImageProcessorRecommendation($collection, ProcessorHelper::PNGQUANT);

        $this->addXdebugMaxNestingLevelIniRecommendation($collection);
        $this->addIntlExtErrorLevelIniRecommendation($collection);
        $this->addShortTagsIniRecommendation($collection);
        $this->addMagicQuotesIniRecommendation($collection);
        $this->addRegisterGlobalsIniRecommendation($collection);
        $this->addSessionAutostartIniRecommendation($collection);
        $this->addRealpathCacheSizeRecommendation($collection);

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addFileInfoOpenRecommendation($collection);
            $this->addComExtRecommendation($collection);
        }
        if (class_exists(Intl::class)) {
            $this->addIntlVersionRecommendation($collection);

            if (Intl::getIcuDataVersion() <= Intl::getIcuVersion()) {
                $this->addIntlIcuDataVersionRecommendation($collection);
            }
        }

        return $collection;
    }

    //PHP ini requirements

    protected function addMemoryLimitIniRequirement(RequirementCollection $collection): void
    {
        $sizeBytes = $this->stringSizeToBytes(ini_get('memory_limit'));

        $collection->addPhpConfigRequirement(
            'memory_limit',
            function () use ($sizeBytes) {
                return $sizeBytes >= 512 * 1024 * 1024 || -1 == $sizeBytes;
            },
            false,
            'memory_limit should be at least 512M',
            'Set the "<strong>memory_limit</strong>" setting in php.ini<a href="#phpini">*</a> to at least "512M".'
        );
    }

    protected function addMbstringOverloadDisabledIniRequirement(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRequirement(
            'mbstring.func_overload',
            function ($cfgValue) {
                return (int)$cfgValue === 0;
            },
            true,
            'string functions should not be overloaded',
            'Set "<strong>mbstring.func_overload</strong>" to <strong>0</strong> in php.ini<a href="#phpini">*</a>' .
                'to disable function overloading by the mbstring extension.'
        );
    }

    protected function addDetectUnicodeIniRequirement(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRequirement('detect_unicode', false);
    }

    protected function addSuhosinWhitelistIniRequirement(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRequirement(
            'suhosin.executor.include.whitelist',
            function ($cfgValue) {
                return false !== stripos($cfgValue, "phar");
            },
            false,
            'suhosin.executor.include.whitelist must be configured correctly in php.ini',
            'Add "<strong>phar</strong>" to <strong>suhosin.executor.include.whitelist</strong> in php.ini' .
                '<a href="#phpini">*</a>.'
        );
    }

    protected function addXdebugShowExceptionTraceIniRequirement(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRequirement(
            'xdebug.show_exception_trace',
            false,
            true
        );
    }

    protected function addXdebugScreamIniRequirement(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRequirement(
            'xdebug.scream',
            false,
            true
        );
    }

    protected function addShortTagsIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation('short_open_tag', false);
    }

    protected function addMagicQuotesIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation('magic_quotes_gpc', false, true);
    }

    protected function addRegisterGlobalsIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation('register_globals', false, true);
    }

    protected function addSessionAutostartIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation('session.auto_start', false);
    }

    //MandatoryRequirements

    protected function addTmpDirWritableRequirement(RequirementCollection $collection): void
    {
        $tmpDir = sys_get_temp_dir();

        $collection->addRequirement(
            is_writable($tmpDir),
            sprintf('%s (sys_get_temp_dir()) directory must be writable', $tmpDir),
            sprintf(
                'Change the permissions of the "<strong>%s</strong>" directory ' .
                'or the result of <string>sys_get_temp_dir()</string> ' .
                'or add the path to php <strong>open_basedir</strong> list. ' .
                'So that it would be writable.',
                $tmpDir
            )
        );
    }

    protected function addFileNameLengthRequirement(RequirementCollection $collection): void
    {
        $addConf = new Process(['addconf', 'NAME_MAX', __DIR__]);

        if (isset($_SERVER['PATH'])) {
            $addConf->setEnv(array('PATH' => $_SERVER['PATH']));
        }
        $addConf->run();

        $collection->addRequirement(
            $addConf->getErrorOutput() || $addConf->addOutput() >= 242,
            'Maximum supported filename length must be greater or equal 242 characters.' .
            ' Make sure that the cache folder is not inside the encrypted directory.',
            'Move <strong>var/cache</strong> folder outside encrypted directory.',
            'Maximum supported filename length must be greater or equal 242 characters.' .
            ' Move var/cache folder outside encrypted directory.'
        );
    }

    protected function addVendorInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            is_dir($this->projectDirectory . '/vendor/composer'),
            'Vendor libraries must be installed',
            'Vendor libraries are missing. Install composer following instructions from' .
            ' <a href="http://addcomposer.org/">http://addcomposer.org/</a>.' .
            ' Then run "<strong>php composer.phar install</strong>" to install them.'
        );
    }

    protected function addIconvExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('iconv'),
            'iconv() must be available',
            'Install and enable the <strong>iconv</strong> extension.'
        );
    }

    protected function addPcntlExtInstalledRequirement(RequirementCollection $collection): void
    {
        if (!\defined('PHP_WINDOWS_VERSION_BUILD')) {
            $collection->addRequirement(
                extension_loaded('pcntl'),
                'pcntl_signal() must be available',
                'Install and enable the <strong>pcntl</strong> extension.'
            );
        }
    }

    protected function addJsonExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('json'),
            'json_encode() must be available',
            'Install and enable the <strong>JSON</strong> extension.'
        );
    }

    protected function addSessionExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            function_exists('session_start'),
            'session_start() must be available',
            'Install and enable the <strong>session</strong> extension.'
        );
    }

    protected function addCtypeExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('ctype'),
            'ctype_alpha() must be available',
            'Install and enable the <strong>ctype</strong> extension.'
        );
    }

    protected function addTokenizerExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('tokenizer'),
            'token_get_all() must be available',
            'Install and enable the <strong>Tokenizer</strong> extension.'
        );
    }

    protected function addSimpleXmlExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('simplexml'),
            'simplexml_import_dom() must be available',
            'Install and enable the <strong>SimpleXML</strong> extension.'
        );
    }

    protected function addApcExtVersionRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            version_compare(phpversion('apc'), '3.1.13', '>='),
            'APC version must be at least 3.1.13',
            'Upgrade your <strong>APC</strong> extension (3.1.13+).'
        );
    }

    protected function addPdoExtInstalledRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('pdo'),
            'PDO should be installed',
            'Install <strong>PDO</strong> (mandatory for Doctrine).'
        );
    }

    protected function addTimeZoneRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            in_array(@date_default_timezone_get(), \DateTimeZone::listIdentifiers(), true),
            sprintf(
                'Configured default timezone "%s" must be supported by your installation of PHP',
                @date_default_timezone_get()
            ),
            'Your default timezone is not supported by PHP. Check for typos in your <strong>php.ini</strong>' .
                ' file and have a look at the list of deprecated timezones at <a href="' .
                'http://php.net/manual/en/timezones.others.php">http://php.net/manual/en/timezones.others.php</a>.'
        );
    }

    //Oro Requirements

    protected function addPhpVersionRequirement(RequirementCollection $collection): void
    {
        $phpVersion = phpversion();

        $collection->addRequirement(
            version_compare($phpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $phpVersion),
            sprintf(
                'You are running PHP version "<strong>%s</strong>", but Oro needs at least PHP "<strong>%s</strong>"' .
                    ' to run. Before using Oro, upgrade your PHP installation, preferably to the latest version.',
                $phpVersion,
                self::REQUIRED_PHP_VERSION
            ),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $phpVersion)
        );
    }

    protected function addGdVersionRequirement(RequirementCollection $collection): void
    {
        $gdVersion = defined('GD_VERSION') ? GD_VERSION : null;

        $collection->addRequirement(
            $gdVersion !== null && version_compare($gdVersion, self::REQUIRED_GD_VERSION, '>='),
            'GD extension must be at least ' . self::REQUIRED_GD_VERSION,
            'Install and enable the <strong>GD</strong> extension at least ' . self::REQUIRED_GD_VERSION . ' version'
        );
    }

    protected function addCurlVersionRequirement(RequirementCollection $collection): void
    {
        $curlVersion = function_exists('curl_version') ? curl_version() : null;

        $collection->addRequirement(
            $curlVersion !== null && version_compare($curlVersion['version'], self::REQUIRED_CURL_VERSION, '>='),
            'cURL extension must be at least ' . self::REQUIRED_CURL_VERSION,
            'Install and enable the <strong>cURL</strong> extension ' .
                'at least ' . self::REQUIRED_CURL_VERSION . ' version'
        );
    }

    protected function addOpenSslExtRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('openssl'),
            'openssl_encrypt() should be available',
            'Install and enable the <strong>openssl</strong> extension.'
        );
    }

    protected function addIconvBehaviorRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            @iconv('utf-8', 'ascii//TRANSLIT', 'check string') !== false,
            'iconv() must not return the false result on converting string "check string"',
            'Check the configuration of the <strong>iconv</strong> extension, '
            . 'as it may have been configured incorrectly'
            . ' (iconv(\'utf-8\', \'ascii//TRANSLIT\', \'check string\') must not return false).'
        );
    }

    protected function addIntlExtRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('intl'),
            'intl extension should be available',
            'Install and enable the <strong>intl</strong> extension.'
        );
    }

    protected function addZipExtRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('zip'),
            'zip extension should be installed',
            'Install and enable the <strong>Zip</strong> extension.'
        );
    }

    protected function addMbstringExtRequirement(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            extension_loaded('mbstring'),
            'mbstring extension should be installed',
            'Install and enable the <strong>mbstring</strong> extension.'
        );
    }

    protected function addImageProcessorsRequirement(RequirementCollection $collection, string $libraryName): void
    {
        [$libraryName, $version] = ProcessorVersionChecker::getLibraryInfo($libraryName);
        $library = null;

        try {
            $library = $this->getImageProcessorLibrary($libraryName);
        } catch (ProcessorsException $exception) {
            $collection->addRequirement(
                null !== $library,
                sprintf('Library `%s` is installed', $libraryName),
                sprintf('Library `%s` not found or not executable.', $libraryName)
            );
        } catch (ProcessorsVersionException $exception) {
            $collection->addRequirement(
                null !== $library,
                sprintf('Minimum required `%s` library version should be %s', $libraryName, $version),
                sprintf('Minimum required `%s` library version should be %s', $libraryName, $version)
            );
        }
    }

    protected function addConfiguredConnectionRequirement(RequirementCollection $collection): void
    {
        try {
            $platformName = $this->connection->getDatabasePlatform()->getName();
        } catch (ConnectionException $exception) {
            $collection->addRequirement(
                false,
                'Database connection is not configured',
                'Database connection is not configured'
            );

            return;
        }
    }

    //Recommendations

    protected function addIcuRecommendation(RequirementCollection $collection): void
    {
        if (!extension_loaded('intl')) {
            return;
        }

        $icuVersion  = Intl::getIcuVersion();
        $localeCurrencies = [
            'de_DE' => 'EUR',
            'en_CA' => 'CAD',
            'en_GB' => 'GBP',
            'en_US' => 'USD',
            'fr_FR' => 'EUR',
            'uk_UA' => 'UAH',
        ];

        foreach ($localeCurrencies as $locale => $currencyCode) {
            $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

            if ($currencyCode === $numberFormatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE)) {
                unset($localeCurrencies[$locale]);
            }
        }

        $collection->addRecommendation(
            empty($localeCurrencies),
            sprintf('Current version %s of the ICU library should meet the requirements', $icuVersion),
            sprintf(
                'There may be a problem with currency formatting in <strong>ICU</strong> %s, ' .
                'please upgrade your <strong>ICU</strong> library.',
                $icuVersion
            )
        );
    }

    protected function addSoapClientRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            class_exists('SoapClient'),
            'SOAP extension should be installed (API calls)',
            'Install and enable the <strong>SOAP</strong> extension.'
        );
    }

    protected function addTidyExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            extension_loaded('tidy'),
            'Tidy extension should be installed to make sure that any HTML is correctly converted ' .
                'into a text representation.',
            'Install and enable the <strong>Tidy</strong> extension.'
        );
    }

    protected function addPharExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            !extension_loaded('phar'),
            'Phar extension is disabled',
            'Disable <strong>Phar</strong> extension to reduce the risk of PHP unserialization vulnerability.'
        );
    }

    protected function addImapExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            extension_loaded('imap'),
            'IMAP extension should be installed for valid email processing on IMAP sync.',
            'Install and enable the <strong>IMAP</strong> extension.'
        );
    }

    protected function addFileInfoOpenRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('finfo_open'),
            'finfo_open() should be available',
            'Install and enable the <strong>Fileinfo</strong> extension.'
        );
    }

    protected function addComExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            class_exists('COM'),
            'COM extension should be installed',
            'Install and enable the <strong>COM</strong> extension.'
        );
    }

    protected function addXdebugMaxNestingLevelIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation(
            'xdebug.max_nesting_level',
            function ($cfgValue) {
                return $cfgValue > 100;
            },
            true,
            'xdebug.max_nesting_level should be above 100 in php.ini',
            'Set "<strong>xdebug.max_nesting_level</strong>" to e.g. "<strong>250</strong>" in ' .
                'php.ini<a href="#phpini">*</a> to stop Xdebug\'s infinite recursion protection erroneously throwing' .
                ' a fatal error in your project.'
        );
    }

    protected function addDomDocumentExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRequirement(
            class_exists('DomDocument'),
            'PHP-DOM and PHP-XML modules should be installed',
            'Install and enable the <strong>PHP-DOM</strong> and the <strong>PHP-XML</strong> modules.'
        );
    }

    protected function addMbstringExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('mb_strlen'),
            'mb_strlen() should be available',
            'Install and enable the <strong>mbstring</strong> extension.'
        );
    }

    protected function addXmlExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('utf8_decode'),
            'utf8_decode() should be available',
            'Install and enable the <strong>XML</strong> extension.'
        );
    }

    protected function addFilterExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('filter_var'),
            'filter_var() should be available',
            'Install and enable the <strong>filter</strong> extension.'
        );
    }

    protected function addPosixExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('posix_isatty'),
            'posix_isatty() should be available',
            'Install and enable the <strong>php_posix</strong> extension (used to colorize the CLI output).'
        );
    }

    protected function addIntlExtErrorLevelIniRecommendation(RequirementCollection $collection): void
    {
        $collection->addPhpConfigRecommendation(
            'intl.error_level',
            function ($cfgValue) {
                return (int)$cfgValue === 0;
            },
            true,
            'intl.error_level should be 0 in php.ini',
            'Set "<strong>intl.error_level</strong>" to "<strong>0</strong>" in php.ini<a href="#phpini">*</a>' .
                ' to inhibit the messages when an error occurs in ICU functions.'
        );
    }

    protected function addAcceleratorExtRecommendation(RequirementCollection $collection): void
    {
        $accelerator =
            (extension_loaded('eaccelerator') && ini_get('eaccelerator.enable'))
            ||
            (extension_loaded('apc') && ini_get('apc.enabled'))
            ||
            (extension_loaded('Zend Optimizer+') && ini_get('zend_optimizerplus.enable'))
            ||
            (extension_loaded('Zend OPcache') && ini_get('opcache.enable'))
            ||
            (extension_loaded('xcache') && ini_get('xcache.cacher'))
            ||
            (extension_loaded('wincache') && ini_get('wincache.ocenabled'))
        ;

        $collection->addRecommendation(
            $accelerator,
            'a PHP accelerator should be installed',
            'Install and/or enable a <strong>PHP accelerator</strong> (highly recommended).'
        );
    }

    protected function addGdExtRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            function_exists('imagewebp'),
            'imagewebp() should be available',
            'Reinstall <strong>GD</strong> extension with WebP image processing enabled.'
        );
    }

    protected function addRealpathCacheSizeRecommendation(RequirementCollection $collection): void
    {
        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            $collection->addRecommendation(
                $this->stringSizeToBytes(ini_get('realpath_cache_size')) >= 5 * 1024 * 1024,
                'realpath_cache_size should be at least 5M in php.ini',
                'Setting "<strong>realpath_cache_size</strong>" to e.g. "<strong>5242880</strong>" or' .
                    ' "<strong>5M</strong>" in php.ini<a href="#phpini">*</a> may improve performance on Windows' .
                    'significantly in some cases.'
            );
        }
    }

    protected function addIntlVersionRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            Intl::getIcuDataVersion() <= Intl::getIcuVersion(),
            sprintf('intl ICU version installed on your system is outdated (%s) and does not match the ICU data' .
                ' bundled with Symfony (%s)', Intl::getIcuVersion(), Intl::getIcuDataVersion()),
            'To get the latest internationalization data upgrade the ICU system package and the intl PHP extension.'
        );
    }

    protected function addIntlIcuDataVersionRecommendation(RequirementCollection $collection): void
    {
        $collection->addRecommendation(
            Intl::getIcuDataVersion() === Intl::getIcuVersion(),
            sprintf(
                'Intl ICU version installed on your system (%s) does not match the ICU data bundled with Symfony (%s)',
                Intl::getIcuVersion(),
                Intl::getIcuDataVersion()
            ),
            'To avoid internationalization data inconsistencies upgrade the symfony/intl component.'
        );
    }

    protected function addImageProcessorRecommendation(RequirementCollection $collection, string $libraryName): void
    {
        $library = null;
        try {
            $library = $this->getImageProcessorLibrary($libraryName);
        } catch (ProcessorsException $exception) {
            return;
        } catch (ProcessorsVersionException $exception) {
            $library = $exception->getBinary();
        }

        $collection->addRecommendation(
            null !== $library,
            sprintf('Library `%s` is installed', $libraryName),
            sprintf('Library `%s` should be installed', $libraryName)
        );
    }

    //Shared

    protected function addPathWritableRequirement(RequirementCollection $collection, string $path): void
    {
        $fullPath = $this->projectDirectory . '/' . $path;
        $pathType = is_file($fullPath) ? 'file' : 'directory';

        $collection->addRequirement(
            is_writable($fullPath),
            $path . ' directory must be writable',
            'Change the permissions of the "<strong>' . $path . '</strong>" ' . $pathType . ' so' .
            ' that the web server can write into it.'
        );
    }

    //Helpers

    protected function stringSizeToBytes($size): int
    {
        $size = str_ireplace(
            ['k', 'kb', 'm', 'mb', 'g', 'gb'],
            [':1', ':1', ':2', ':2', ':3', ':3'],
            $size
        );
        $sizeParts = explode(':', $size);

        if (count($sizeParts) === 2) {
            return $sizeParts[0] * pow(1024, $sizeParts[1]);
        }

        return (int)$size;
    }

    protected function getImageProcessorLibrary(string $libraryName): ?string
    {
        return $libraryName === ProcessorHelper::JPEGOPTIM
            ? $this->processorHelper->getJPEGOptimLibrary()
            : $this->processorHelper->getPNGQuantLibrary();
    }
}
