<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Repository;

use Doctrine\DBAL\Connection;

/**
 * Repository for managing silenced failure cases in Behat tests.
 * It checks if a case is silenced based on its title and error details,
 * and allows incrementing the silenced count for a case.
 */
class SilencedFailureRepository
{
    public function __construct(private Connection $connection)
    {
    }

    public function isSilencedCase(string $title, string $errorDetails): bool
    {
        $title = $this->normalizeTitle($title);
        $errorDetails = $this->normalizeErrorDetails($errorDetails);
        return $this->connection
            ->createQueryBuilder()
            ->select('COUNT(*) > 0')
            ->from('silenced_cases')
            ->where('name = :name')
            ->andWhere('errorDetails = :errorDetails')
            ->setParameter('name', $title)
            ->setParameter('errorDetails', $errorDetails)
            ->execute()
            ->fetchOne();
    }

    private function normalizeErrorDetails(string $errorDetails): string
    {
        if (!$errorDetails || trim($errorDetails) === '') {
            return '';
        }

        $lines = explode("\n", $errorDetails);
        $firstLine = trim($lines[0]);
        $firstLine = preg_replace(
            [
                '/uid-[a-zA-Z0-9]+/',          // anonymise random UIDs
                '/at point \(\d+, \d+\)/',     // anonymise coordinates
                '/\\\\/'                       // strip escaped backslashes
            ],
            ['uid-DYNAMIC', 'at point (DYNAMIC)', ''],
            $firstLine
        );
        return \substr($firstLine, 0, 1024);
    }

    private function normalizeTitle(string $title): ?string
    {
        return \substr($title, 0, 1024);
    }
}
