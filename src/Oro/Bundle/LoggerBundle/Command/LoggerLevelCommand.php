<?php
declare(strict_types=1);

namespace Oro\Bundle\LoggerBundle\Command;

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
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Temporarily changes the configured logging level.
 */
class LoggerLevelCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:logger:level';

    private ConfigManager $globalConfigManager;
    private ConfigManager $userConfigManager;
    private CacheInterface $cache;
    private UserManager $userManager;

    protected static array $loggingLevels = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function __construct(
        ConfigManager  $globalConfigManager,
        ConfigManager  $userConfigManager,
        CacheInterface $cache,
        UserManager    $userManager
    ) {
        parent::__construct();

        $this->globalConfigManager = $globalConfigManager;
        $this->userConfigManager = $userConfigManager;
        $this->cache = $cache;
        $this->userManager = $userManager;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument('level', InputArgument::REQUIRED, 'Log level (warning, notice, info, debug)')
            ->addArgument('disable-after', InputArgument::REQUIRED, 'Disable logging after specified time interval')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Email of existing user')
            ->setDescription('Temporarily changes the configured logging level.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command temporarily increases the configured logging level for
the specified duration of time. The second argument accepts any relative date/time
format recognized by PHP (<comment>https://php.net/manual/datetime.formats.relative.php</comment>).

  <info>php %command.full_name% <level> <disable-after></info>

The <info>--user</info> option can be used to modify the logger level to the configuration
scope of a specific user (the global configuration is changed otherwise):

  <info>php %command.full_name% --user=<user-email> <level> <disable-after></info>

HELP
            )
            ->addUsage('--user=<user-email> <level> <disable-after>');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $level = $this->getLogLevel($input->getArgument('level'));
        $disableAfter = $this->getDisableAfterDateTime($input->getArgument('disable-after'));

        $user = $this->getUser($input->getOption('user'));

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

        $this->cache->delete(Configuration::LOGS_LEVEL_KEY);

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

        return 0;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getLogLevel(string $value): string
    {
        if (!in_array($value, self::$loggingLevels)) {
            throw new \InvalidArgumentException(
                \sprintf("Wrong '%s' value for '%s' argument", $value, 'level')
            );
        }

        return $value;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getDisableAfterDateTime(string $value): \DateTime
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $disableAfter = clone $now;

        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        $interval = @\DateInterval::createFromDateString($value);
        if ($interval instanceof \DateInterval) {
            $disableAfter->add($interval);
        }

        if ($disableAfter <= $now) {
            throw new \InvalidArgumentException(
                \sprintf("Value '%s' for '%s' argument should be valid date interval", $value, 'disable-after')
            );
        }

        $afterHour = clone $now;
        $afterHour->add(new \DateInterval('PT1H'));
        if ($disableAfter > $afterHour) {
            throw new \InvalidArgumentException(
                \sprintf("Value '%s' should be less than an hour", 'disable-after')
            );
        }

        return $disableAfter;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getUser(?string $email): ?User
    {
        $user = null;

        if ($email) {
            $user = $this->userManager->findUserByEmail($email);
            if (is_null($user)) {
                throw new \InvalidArgumentException(\sprintf("User with email '%s' not exists.", $email));
            }
        }
        /** @var User $user */
        return $user;
    }
}
