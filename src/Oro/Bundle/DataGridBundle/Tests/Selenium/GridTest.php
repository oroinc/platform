<?php

namespace Oro\Bundle\DataGridBundle\Tests\Selenium;

use Oro\Bundle\UserBundle\Tests\Selenium\Pages\Users;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

/**
 * Class GridTest
 *
 * @package Oro\Bundle\DataGridBundle\Tests\Selenium
 */
class GridTest extends Selenium2TestCase
{
    public function testSelectPage()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');
        //check count of users, continue only for BAP
        if ($login->getPagesCount() == 1) {
            $this->markTestSkipped("Test skipped for current environment");
        }
        $userData = $login->getRandomEntity();
        static::assertTrue($login->entityExists($userData));
        $login = $login->changePage(2);
        static::assertFalse($login->entityExists($userData));
        $login = $login->changePage(1);
        static::assertTrue($login->entityExists($userData));
    }

    public function testNextPage()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');

        //check count of users, continue only for BAP
        if ($login->getPagesCount() === 1) {
            static::markTestSkipped("Test skipped for current environment");
        }
        $userData = $login->getRandomEntity();
        static::assertTrue($login->entityExists($userData));
        $login = $login->nextPage();
        static::assertFalse($login->entityExists($userData));
        $login = $login->previousPage();
        static::assertTrue($login->entityExists($userData));
    }

    public function testPrevPage()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');
        //check count of users, continue only for BAP
        if ($login->getPagesCount() === 1) {
            static::markTestSkipped("Test skipped for current environment");
        }
        $userData = $login->getRandomEntity();
        static::assertTrue($login->entityExists($userData));
        $login = $login->nextPage();
        static::assertFalse($login->entityExists($userData));
        $login = $login->previousPage();
        static::assertTrue($login->entityExists($userData));
    }

    /**
     * @param $filterName
     * @param $condition
     *
     * @dataProvider filterData
     */
    public function testFilterBy($filterName, $condition)
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');
        $userData = $login->getRandomEntity();
        static::assertTrue(
            $login->filterBy($filterName, $userData[strtoupper($filterName)], $condition)
                ->entityExists($userData)
        );
        static::assertEquals(1, $login->getRowsCount());
        $login->clearFilter($filterName);
    }

    /**
     * Data provider for filter tests
     *
     * @return array
     */
    public function filterData()
    {
        return array(
            //'ID' => array('ID', '='),
            'Username' => array('Username', 'is equal to'),
            'Email' => array('Primary Email', 'contains'),
            //'First name' => array('First name', 'is equal to'),
            //'Birthday' => array('Birthday', '')
        );
    }

    public function testAddFilter()
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');

        $userData = $login ->getRandomEntity();
        static::assertTrue($login ->entityExists($userData));
        $countOfRecords = $login ->getRowsCount();
        static::assertEquals(
            $countOfRecords,
            $login->getRowsCount()
        );

        static::assertEquals(
            1,
            $login->addFilter('Primary Email')
                ->filterBy('Primary Email', $userData[strtoupper('Primary Email')], 'is equal to')
                ->getRowsCount()
        );
    }

    /**
     * Tests that order in columns works correct
     *
     * @param string $columnName
     * @dataProvider columnTitle
     */
    public function testSorting($columnName)
    {
        $login = $this->login();
        /** @var Users $login */
        $login = $login->openUsers('Oro\Bundle\UserBundle');
        //check count of users, continue only for BAP
        if ($login->getPagesCount() === 1) {
            static::markTestSkipped("Test skipped for current environment");
        }
        $login->changePageSize('last');
        $columnId = $login->getColumnNumber($columnName);

        //test descending order
        $columnOrder = $login->sortBy($columnName, 'desc')->getColumn($columnId);

        if ($columnName === 'Birthday') {
            $dateArray = array();
            foreach ($columnOrder as $value) {
                $date = strtotime($value);
                $dateArray[] = $date;
            }
            $columnOrder = $dateArray;
        }
        $sortedColumnOrder = $columnOrder;
        sort($sortedColumnOrder);
        $sortedColumnOrder = array_reverse($sortedColumnOrder);

        static::assertEquals(
            $sortedColumnOrder,
            $columnOrder,
            print_r(array('expected' => $sortedColumnOrder, 'actual' => $columnOrder), true)
        );
        //change page size to 10 and refresh grid
        $login = $login->changePageSize('first');
        $login = $login->sortBy($columnName, 'asc');
        $columnOrder = $login->sortBy($columnName, 'desc')->getColumn($columnId);
        static::assertTrue(
            $columnOrder === array_slice($sortedColumnOrder, 0, 10),
            print_r(array('expected' => $sortedColumnOrder, 'actual' => $columnOrder), true)
        );

        //test ascending order
        $login = $login->changePageSize('last');
        $columnOrder = $login->sortBy($columnName, 'asc')->getColumn($columnId);

        if ($columnName === 'Birthday') {
            $dateArray = array();
            foreach ($columnOrder as $value) {
                $date = strtotime($value);
                $dateArray[] = $date;
            }
            $columnOrder = $dateArray;
        }
        $sortedColumnOrder = $columnOrder;
        natcasesort($sortedColumnOrder);

        static::assertTrue(
            $columnOrder === $sortedColumnOrder,
            print_r(array('expected' => $sortedColumnOrder, 'actual' => $columnOrder), true)
        );
        //change page size to 10 and refresh grid
        $login = $login->changePageSize('first');
        $login = $login->sortBy($columnName, 'desc');
        $columnOrder = $login->sortBy($columnName, 'asc')->getColumn($columnId);
        static::assertTrue(
            $columnOrder === array_slice($sortedColumnOrder, 0, 10),
            print_r(array('expected' => $sortedColumnOrder, 'actual' => $columnOrder), true)
        );
    }

    /**
     * Data provider for test sorting
     *
     * @return array
     */
    public function columnTitle()
    {
        return array(
            //'ID' => array('ID'),
            'Username' => array('Username'),
            //'Email' => array('Email'),
            //'First name' => array('First name'),
            //'Birthday' => array('Birthday'),
            //'Company' => array('Company'),
            //'Salary' => array('Salary'),
        );
    }
}
