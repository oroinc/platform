<?php

namespace Oro\Bundle\NoteBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class Notes
 *
 * @package Oro\Bundle\NoteBundle\Pages\Objects
 * @method Notes openNotes() openTags(string)
 * {@inheritdoc}
 */
class Notes extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $tagName;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    /**
     * @return $this
     */
    public function addNote()
    {
        $this->assertElementPresent(
            "//div[@class='pull-right title-buttons-container']//a[@id='add-entity-note-button']",
            'Add Note button not available'
        );
        $this->test->byXPath(
            "//div[@class='pull-right title-buttons-container']//a[@id='add-entity-note-button']"
        )->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix']".
            "/span[normalize-space(.)='Add note']",
            'Add Note window is not opened'
        );

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function setNoteMessage($note)
    {
        $this->$note = $this->test->byId('oro_note_form_message');
        $this->$note->clear();
        $this->$note->value($note);

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
        $this->assertElementPresent(
            "//div[@class='pull-right title-buttons-container']//a[@id='add-entity-note-button']",
            'Add Note button not available'
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function addNoteButtonNotAvailable()
    {
        $this->assertElementNotPresent(
            "//div[@class='pull-right title-buttons-container']//a[@id='add-entity-note-button']",
            'Add Note button is available'
        );

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function checkNote($note)
    {
        $this->assertElementPresent(
            "//div[@class='title'][span[normalize-space(.)='Notes']]/following-sibling::div".
            "//div[starts-with(@id,'accordion-item')][contains(., '{$note}')]",
            'Note not found'
        );

        return $this;
    }

    /**
     * @param string $note
     * @return $this
     */
    public function editNote($note)
    {
        $this->test->byXPath(
            "//div[@class='title'][span[normalize-space(.)='Notes']]/following-sibling::div".
            "//div[starts-with(@id,'accordion-item')][contains(., '{$note}')]".
            "/preceding-sibling::div/div[@class='actions']/button[@title='Edit note']"
        )->click();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix']".
            "/span[normalize-space(.)='Update note']",
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
        $this->test->byXPath(
            "//div[@class='title'][span[normalize-space(.)='Notes']]/following-sibling::div".
            "//div[starts-with(@id,'accordion-item')][contains(., '{$note}')]".
            "/preceding-sibling::div/div[@class='actions']/button[@title='Remove note']"
        )->click();
        $this->test->byXpath("//div[div[contains(., 'Delete Confirmation')]]//a[text()='Yes, Delete']")->click();
        $this->waitForAjax();

        return $this;
    }
}
