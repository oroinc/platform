<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\TokenGenerator;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class FtpHandler implements ArtifactsHandlerInterface
{
    /**
     * @var Resource|false
     */
    protected $ftpConnection;

    /**
     * @var string
     */
    protected $ftpHost;

    /**
     * @var string
     */
    protected $ftpUsername;

    /**
     * @var string
     */
    protected $ftpPassword;

    /**
     * @var string
     */
    protected $ftpDirectory;

    /**
     * @var string
     */
    protected $screenshotRemoteBaseUrl;

    /**
     * @param array $ftpConfig
     */
    public function __construct(array $ftpConfig)
    {
        $this->ftpHost = $this->getConfigValue($ftpConfig, 'host', true);
        $this->ftpUsername = $this->getConfigValue($ftpConfig, 'username');
        $this->ftpPassword = $this->getConfigValue($ftpConfig, 'password');
        $this->ftpDirectory = $this->getConfigValue($ftpConfig, 'directory', 'true');
        $this->screenshotRemoteBaseUrl = trim(
            $this->getConfigValue($ftpConfig, 'base_url', true),
            " \t\n\r\0\x0B\\"
        );
    }

    /**
     * {@inheritdoc}
     */
    public function save($data)
    {
        $fileName = TokenGenerator::generateToken('image').'.png';
        $localFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;
        file_put_contents($localFile, $data);

        if (!$ftpConnection = $this->getFtpConnection()) {
            return "There is issue with ftp connection";
        }

        if (!ftp_put($ftpConnection, $fileName, $localFile, FTP_BINARY)) {
            return "There was a problem while uploading screenshot to ftp server\n";
        }

        ftp_chmod($ftpConnection, 0644, $fileName);
        ftp_close($ftpConnection);

        return rtrim($this->screenshotRemoteBaseUrl, '/').'/'.trim($fileName, '/');
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'ftp';
    }

    /**
     * @return false|resource
     */
    private function getFtpConnection()
    {
        $connection = ftp_connect($this->ftpHost);

        if (!ftp_login($connection, $this->ftpUsername, $this->ftpPassword)) {
            $connection = null;
            return false;
        }

        ftp_chdir($connection, $this->ftpDirectory);
        ftp_pasv($connection, true);

        return $connection;
    }

    /**
     * @param array $config
     * @param string $key
     * @param bool $isMandatory
     * @return mixed|null|string
     */
    private function getConfigValue(array $config, $key, $isMandatory = false)
    {
        if (!array_key_exists($key, $config)) {
            if ($isMandatory) {
                throw new InvalidArgumentException(sprintf(
                    'Setting "%s" is mandatory',
                    $key
                ));
            }

            return null;
        }

        $value = $config[$key];

        if (!is_string($value)) {
            return $value;
        }

        if (0 === strpos($value, 'env(') && ')' === substr($value, -1) && 'env()' !== $value) {
            $value = getenv(substr($value, 4, -1));
        }

        return $value;
    }
}
