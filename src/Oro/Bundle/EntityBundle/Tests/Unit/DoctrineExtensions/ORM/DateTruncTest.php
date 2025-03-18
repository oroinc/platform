<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DoctrineExtensions\ORM;

use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\Testing\Unit\ORM\Mocks\EntityManagerMock;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use Symfony\Component\Yaml\Parser;

final class DateTruncTest extends OrmTestCase
{
    protected EntityManagerMock $em;

    #[\Override]
    public function setUp(): void
    {
        $metadataDriver = new AttributeDriver(['Oro\Bundle\DashboardBundle\Entity']);

        $this->em = $this->getTestEntityManager();
        $configuration = $this->em->getConfiguration();
        $configuration->setMetadataDriverImpl($metadataDriver);
        $configuration->setEntityNamespaces(['OroDashboardBundle' => 'Oro\Bundle\DashboardBundle\Entity']);

        $parser = new Parser();
        // Load the corresponding config file.
        $config = $parser->parse(\file_get_contents(\realpath(__DIR__ . '/../../../../Resources/config/oro/app.yml')));
        $parsed = $config['doctrine']['orm']['dql'];

        // Load the existing function classes.
        if (\array_key_exists('datetime_functions', $parsed)) {
            foreach ($parsed['datetime_functions'] as $key => $value) {
                $configuration->addCustomDatetimeFunction(\strtoupper($key), $value);
            }
        }
    }

    public function testDateTrunc(): void
    {
        $queryBuilder = new QueryBuilder($this->em);
        $queryBuilder->select("date_trunc('YEAR', d.createdAt)")
            ->from('Oro\Bundle\DashboardBundle\Entity\Dashboard', 'd');

        self::assertSame(
            'SELECT DATE_TRUNC(\'YEAR\', o0_.createdAt) AS sclr_0 FROM oro_dashboard o0_',
            $queryBuilder->getQuery()->getSQL()
        );
    }
}
