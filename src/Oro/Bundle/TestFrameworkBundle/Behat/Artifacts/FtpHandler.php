<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

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
        $this->ftpHost = $ftpConfig['host'];
        $this->ftpUsername = $ftpConfig['username'];
        $this->ftpPassword = $ftpConfig['password'];
        $this->ftpDirectory = $ftpConfig['directory'];
        $this->screenshotRemoteBaseUrl = trim($ftpConfig['base_url'], " \t\n\r\0\x0B\\");
    }

    /**
     * {@inheritdoc}
     */
    public function save($data)
    {
        $fileName = uniqid().'.png';
        $localFile = sys_get_temp_dir().'/'.$fileName;
        file_put_contents($localFile, $data);

        if (!$ftpConnection = $this->getFtpConnection()) {
            echo "There is issue with ftp connection";
        }

        if (!ftp_put($ftpConnection, $fileName, $localFile, FTP_BINARY)) {
            return "There was a problem while uploading screenshot to ftp server\n";
        }

        ftp_chmod($ftpConnection, 0644, $fileName);
        ftp_close($ftpConnection);

        return $this->screenshotRemoteBaseUrl.'/'.$fileName;
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
}
