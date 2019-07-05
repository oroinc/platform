<?php

namespace Oro\Bundle\LoggerBundle\Command;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates logger level configuration.
 */
class LoggerLevelCommand extends Command
{
    const LEVEL_PARAM         = 'level';
    const DISABLE_AFTER_PARAM = 'disable-after';
    const USER_PARAM          = 'user';

    /** @var string */
    protected static $defaultName = 'oro:logger:level';

    /** @var ConfigManager */
    private $globalConfigManager;

    /** @var ConfigManager */
    private $userConfigManager;

    /** @var CacheProvider */
    private $cache;

    /** @var UserManager */
    private $userManager;

    /** @var array */
    protected static $loggingLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    /**
     * @param ConfigManager $globalConfigManager
     * @param ConfigManager $userConfigManager
     * @param CacheProvider $cache
     * @param UserManager $userManager
     */
    public function __construct(
        ConfigManager $globalConfigManager,
        ConfigManager $userConfigManager,
        CacheProvider $cache,
        UserManager $userManager
    ) {
        parent::__construct();

        $this->globalConfigManager = $globalConfigManager;
        $this->userConfigManager = $userConfigManager;
        $this->cache = $cache;
        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument(
                self::LEVEL_PARAM,
                InputArgument::REQUIRED,
                'Log level (warning|notice|info|debug)'
            )
            ->addArgument(
                self::DISABLE_AFTER_PARAM,
                InputArgument::REQUIRED,
                'Disable logging after interval. For example: "630 seconds", "1 hour + 30 minutes", etc. ' .
                'See: <info>http://php.net/manual/en/datetime.formats.relative.php<info>'
            )
            ->addOption(
                self::USER_PARAM,
                'u',
                InputOption::VALUE_REQUIRED,
                'Email of existing user'
            )
            ->setDescription('Update logger level configuration')
            ->setHelp(
                'If “user” param is not set - update global scope, otherwise update user scope'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = null;
        $level = $this->getLogLevel($input->getArgument(self::LEVEL_PARAM));
        $disableAfter = $this->getDisableAfterDateTime($input->getArgument(self::DISABLE_AFTER_PARAM));

        $user = $this->getUser($input->getOption(self::USER_PARAM));

        if ($user) {
            $configManager = $this->userConfigManager;
            $configManager->setScopeIdFromEntity($user);
        } else {
            $configManager = $this->globalConfigManager;
        }

        $configManager->set(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY), $level);
        $configManager->set(
            Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY),
            $disableAfter->getTimestamp()
        );

        $configManager->flush();

        if ($this->cache->contains(Configuration::LOGS_LEVEL_KEY)) {
            $this->cache->delete(Configuration::LOGS_LEVEL_KEY);
        }

        if ($user) {
            $message = sprintf(
                "Log level for user '%s' is successfully set to '%s' till %s.",
                $user->getEmail(),
                $level,
                $disableAfter->format(\DateTime::RFC850)
            );
        } else {
            $message = sprintf(
                "Log level for global scope is set to '%s' till %s.",
                $level,
                $disableAfter->format(\DateTime::RFC850)
            );
        }

        $output->writeln($message);
    }

    /**
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getLogLevel($value)
    {
        if (!in_array($value, self::$loggingLevels)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Wrong '%s' value for '%s' argument",
                    $value,
                    self::LEVEL_PARAM
                )
            );
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @throws \InvalidArgumentException
     *
     * @return \DateTime
     */
    public function getDisableAfterDateTime($value)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter = clone $now;

        // Starting from 7.1.28 and 7.2.17 PHP versions, a PHP Warning will be thrown if the value is not correct.
        // Disable error reporting to display own error without PHP Warning.
        $errorLevel = error_reporting();
        error_reporting(0);
        $disableAfter->add(\DateInterval::createFromDateString($value));
        error_reporting($errorLevel);

        if ($disableAfter <= $now) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Value '%s' for '%s' argument should be valid date interval",
                    $value,
                    self::DISABLE_AFTER_PARAM
                )
            );
        }

        return $disableAfter;
    }

    /**
     * @param string $email
     *
     * @throws \InvalidArgumentException
     *
     * @return User|null
     */
    public function getUser($email)
    {
        $user = null;

        if ($email) {
            /** @var User $user */
            $user = $this->userManager->findUserByEmail($email);

            if (is_null($user)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "User with email '%s' not exists.",
                        $email
                    )
                );
            }
        }

        return $user;
    }
}
