<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Functional\Command;

use Oro\Bundle\InstallerBundle\Provider\PlatformRequirementsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CheckRequirementsCommandTest extends WebTestCase
{
    public function testIniRequirementsNotFulfilled()
    {
        $this->bootKernel();

        $output = $this->executeCommand(
            [
                '-d apc.enabled=0',
                '-d memory_limit=256M',
                '-d detect_unicode=1',
                '-d short_open_tag=1',
                '-d session.auto_start=1',
                '-d eaccelerator.enable=0',
                '-d apc.enabled=0',
                '-d zend_optimizerplus.enable=0',
                '-d opcache.enable=0',
                '-d xcache.cacher=0',
                '-d wincache.ocenabled=0',
            ]
        );
        $messages = array_merge(
            $this->parseMessages($output, 'ERROR'),
            $this->parseMessages($output, 'WARNING'),
        );

        $errorMessage = 'Command Output: '.$output;
        $this->assertContains(
            'Set the "memory_limit" setting in php.ini* to at least "512M".',
            $messages,
            $errorMessage
        );
        $this->assertContains(
            'Install and/or enable a PHP accelerator (highly recommended).',
            $messages,
            $errorMessage
        );
        $this->assertContains('Set short_open_tag to off in php.ini*.', $messages, $errorMessage);
        $this->assertContains('Set session.auto_start to off in php.ini*.', $messages, $errorMessage);
        $this->assertContains(
            'Install and/or enable a PHP accelerator (highly recommended).',
            $messages,
            $errorMessage
        );
    }

    public function testIniRequirementsFulfilled()
    {
        $this->bootKernel();

        $output = $this->executeCommand();
        $messages = $this->parseMessages($output, 'OK');

        $gdVersion = PlatformRequirementsProvider::REQUIRED_GD_VERSION;
        $curlVersion = PlatformRequirementsProvider::REQUIRED_CURL_VERSION;

        $errorMessage = 'Command Output: '.$output;
        $this->assertContains('iconv() must be available', $messages, $errorMessage);
        $this->assertContains('ctype_alpha() must be available', $messages, $errorMessage);
        $this->assertContains('token_get_all() must be available', $messages, $errorMessage);
        $this->assertContains('simplexml_import_dom() must be available', $messages, $errorMessage);
        $this->assertContains('memory_limit should be at least 512M', $messages, $errorMessage);
        $this->assertContains('detect_unicode must be disabled in php.ini', $messages, $errorMessage);
        $this->assertContains('GD extension must be at least '.$gdVersion, $messages, $errorMessage);
        $this->assertContains('cURL extension must be at least '.$curlVersion, $messages, $errorMessage);
        $this->assertContains('openssl_encrypt() should be available', $messages, $errorMessage);
        $this->assertContains('intl extension should be available', $messages, $errorMessage);
        $this->assertContains('zip extension should be installed', $messages, $errorMessage);
        $this->assertContains('openssl_encrypt() should be available', $messages, $errorMessage);
        $this->assertContains('mb_strlen() should be available', $messages, $errorMessage);
        $this->assertContains('imagewebp() should be available', $messages, $errorMessage);
    }

    private function executeCommand(array $args = []): string
    {
        $finder = new PhpExecutableFinder();
        $phpBinary = $finder->find();
        $command = [$phpBinary, ...$args, 'bin/console', 'oro:check-requirements', '-etest', '-vvv'];

        $process = new Process($command);
        $process->run();

        return $process->getOutput()."\n".$process->getErrorOutput();
    }

    private function parseMessages(string $output, string $prefix): array
    {
        $regexp = '/' . $prefix . '\s+\|(.*?)\|(\n|\r\n)/s';

        if (preg_match_all($regexp, $output, $matches)) {
            return array_map('trim', $matches[1]);
        }

        return [];
    }
}
