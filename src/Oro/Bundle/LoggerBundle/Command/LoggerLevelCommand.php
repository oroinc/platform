<?php

namespace Oro\Bundle\LoggerBundle\Command;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LoggerBundle\DependencyInjection\Configuration;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Log\LogLevel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoggerLevelCommand extends ContainerAwareCommand
{
    const LEVEL_PARAM         = 'level';
    const DISABLE_AFTER_PARAM = 'disable-after';
    const USER_PARAM          = 'user';

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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:logger:level')
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
            /* @var ConfigManager $configManager */
            $configManager = $this->getContainer()->get('oro_config.user');
            $configManager->setScopeIdFromEntity($user);
        } else {
            /* @var ConfigManager $configManager */
            $configManager = $this->getContainer()->get('oro_config.global');
        }

        $configManager->set(Configuration::getFullConfigKey(Configuration::LOGS_LEVEL_KEY), $level);
        $configManager->set(
            Configuration::getFullConfigKey(Configuration::LOGS_TIMESTAMP_KEY),
            $disableAfter->getTimestamp()
        );

        $configManager->flush();

        /** @var CacheProvider $cache */
        $cache = $this->getContainer()->get('oro_logger.cache');
        if ($cache->contains(Configuration::LOGS_LEVEL_KEY)) {
            $cache->delete(Configuration::LOGS_LEVEL_KEY);
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
        $disableAfter->add(\DateInterval::createFromDateString($value));

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
            $user = $this
                ->getContainer()
                ->get('oro_user.manager')
                ->findUserByEmail($email);

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
