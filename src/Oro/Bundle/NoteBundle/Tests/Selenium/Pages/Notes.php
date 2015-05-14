<?php

namespace Oro\Bundle\NoteBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Notes
 *
 * @package Oro\Bundle\NoteBundle\Pages\Objects
 * @method Notes openNotes() openNotes(string)
 * {@inheritdoc}
 */
class Notes extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tagName;

    /**
     * @return $this
     */
    public function addNote()
    {
        if ($this->isElementPresent("//div[@class='pull-right']//a[@class='btn dropdown-toggle']")) {
            $this->runActionInGroup('Add note');
        } else {
            $this->test->byXPath(
                "//div[@class='pull-right title-buttons-container']//a[starts-with(@id,'add-entity-note-button')]"
            )->click();
            $this->waitForAjax();
            $this->assertElementPresent(
                "//div[contains(@class,'ui-dialog-titlebar')]".
                "/span[normalize-space(.)='Add note']",
                'Add Note window is not opened'
            );
        }

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNoteMessage($note)
    {
        $this->test->waitUntil(
            function (\PHPUnit_Extensions_Selenium2TestCase $testCase) {
                return $testCase->execute(
                    [
                        'script' => 'return tinyMCE.activeEditor.initialized',
                        'args' => [],
                    ]
                );
            },
            intval(MAX_EXECUTION_TIME)
        );

        $this->test->execute(
            [
                'script' => sprintf('tinyMCE.activeEditor.setContent(\'%s\')', $note),
                'args' => [],
            ]
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function saveNote()
    {
        $this->test->byXPath("//div[@class='widget-actions-section']//button[@type='submit']")->click();
        $this->waitForAjax();

        return $this;
    }

    /**
     * @return $this
     */
    public function addNoteButtonAvailable()
    {
        if ($this->isElementPresent("//div[@class='pull-right']//a[@class='btn dropdown-toggle']")) {
            $this->checkActionInGroup(['Add note']);
        } else {
            $this->assertElementPresent(
                "//div[@class='pull-right title-buttons-container']//a[starts-with(@id,'add-entity-note-button')]",
                'Add Note button not available'
            );
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function addNoteButtonNotAvailable()
    {
        if ($this->isElementPresent("//div[@class='pull-right']//a[@class='btn dropdown-toggle']")) {
            $this->checkActionInGroup(['Add note'], false);
        } else {
            $this->assertElementNotPresent(
                "//div[@class='pull-right title-buttons-container']//a[starts-with(@id,'add-entity-note-button')]",
                'Add Note button is available'
            );
        }

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function checkNote($note)
    {
        $this->verifyActivity('Note', $note);
        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function editNote($note)
    {
        $actionMenu = "//*[@class='container-fluid accordion']".
            "//*[@class='message-item message'][contains(., '{$note}')]".
            "/parent::*/*[@class='actions']//a[contains(., '...')]";
        $editAction =
            "//*[@class='dropdown-menu activity-item pull-right launchers-dropdown-menu']".
            "//a[@title='Update Note']";
        // hover will show menu, 1st click - will hide, 2nd - will show again
        $this->test->byXPath($actionMenu)->click();
        $this->test->byXPath($actionMenu)->click();
        $this->test->byXPath($editAction)->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]".
            "/span[normalize-space(.)='{$note}']",
            'Update Note window is not opened'
        );

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function deleteNote($note)
    {
        $actionMenu = "//*[@class='container-fluid accordion']".
            "//*[@class='message-item message'][contains(., '{$note}')]".
            "/parent::*/*[@class='actions']//a[contains(., '...')]";
        $deleteAction =
            "//*[@class='dropdown-menu activity-item pull-right launchers-dropdown-menu']".
            "//a[@title='Delete Note']";
        // hover will show menu, 1st click - will hide, 2nd - will show again
        $this->test->byXPath($actionMenu)->click();
        $this->test->byXPath($actionMenu)->click();
        $this->test->byXPath($deleteAction)->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitForAjax();

        return $this;
    }
}
