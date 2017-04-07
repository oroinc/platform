<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

class FtpHandler implements ArtifactsHandlerInterface
{
    /**
     * @var Resource|false
     */
    protected $ftpConnection;

    protected $ftpConfig;

    /**
     * @var string
     */
    protected $screenshotRemoteBaseUrl;

    public function __construct(array $ftpConfig)
    {
        $this->ftpConfig = $ftpConfig;
        $this->ftpConnection = ftp_connect($ftpConfig['host']);

        if (!ftp_login($this->ftpConnection, $ftpConfig['username'], $ftpConfig['password'])) {
            $this->ftpConnection = null;
            echo "Can't connect to ftp server";
            return;
        }

        ftp_chdir($this->ftpConnection, $ftpConfig['directory']);
        ftp_pasv($this->ftpConnection, true);
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

        if (!ftp_put($this->ftpConnection, $fileName, $localFile, FTP_BINARY)) {
            echo "There was a problem while uploading $data\n";
        }

        ftp_chmod($this->ftpConnection, 0644, $fileName);

        return $this->screenshotRemoteBaseUrl.'/'.$fileName;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfigKey()
    {
        return 'ftp';
    }

    public function __destruct()
    {
        if (!$this->ftpConnection) {
            return;
        }

        ftp_close($this->ftpConnection);
    }
}
