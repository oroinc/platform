<?php

namespace Oro\Bundle\DataGridBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;

interface GridInterface extends GridMappedChildInterface
{
    /**
     * @param string $title
     * @throws \Exception
     */
    public function clickMassActionLink($title);

    /**
     * @param string $title
     * @throws \Exception
     */
    public function clickSelectAllMassActionLink($title);

    public function clickViewList();

    /**
     * @param int $number
     * @param int $cellNumber
     */
    public function checkFirstRecords($number, $cellNumber = 0);

    /**
     * @param int $number
     * @param int $cellNumber
     */
    public function uncheckFirstRecords($number, $cellNumber = 0);

    /**
     * @param string $content
     */
    public function checkRecord($content);

    /**
     * @param string $content
     */
    public function uncheckRecord($content);

    /**
     * @return NodeElement
     * @throws \Exception
     */
    public function getMassActionButton();

    /**
     * @param string $title
     * @return NodeElement|null
     */
    public function getMassActionLink($title);

    /**
     * Checks if a grid has a mass action with a given $title.
     *
     * @param string $title
     * @return bool
     */
    public function hasMassActionLink($title): bool;

    /**
     * @param string $title
     * @throws \Exception
     */
    public function massCheck($title);

    /**
     * @param int $number
     * @throws ElementNotFoundException
     */
    public function selectPageSize($number);

    /**
     * @param string $content
     * @param string $action
     */
    public function clickActionLink($content, $action);

    /**
     * @return NodeElement
     */
    public function getViewList();
}
