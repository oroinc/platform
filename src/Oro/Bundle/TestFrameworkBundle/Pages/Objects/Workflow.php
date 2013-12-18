<?php

namespace Oro\Bundle\TestFrameworkBundle\Pages\Objects;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractEntity;
use Oro\Bundle\TestFrameworkBundle\Pages\Entity;

class Workflow extends AbstractEntity implements Entity
{
    protected $contact;
    protected $account;
    protected $budget;
    protected $probability;
    protected $customerNeed;
    protected $solution;

    public function __construct($testCase, $redirect = true)
    {
        parent::__construct($testCase, $redirect);
    }

    public function setContact($contact)
    {
        $this->byXpath("//div[@id='s2id_oro_workflow_step_contact']/a")->click();
        $this->waitForAjax();
        $this->byXpath("//div[@id='select2-drop']/div/input")->value($contact);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$contact}')]",
            "Owner autocoplete doesn't return search value"
        );
        $this->byXpath("//div[@id='select2-drop']//div[contains(., '{$contact}')]")->click();

        return $this;
    }

    public function setAccount($account)
    {
        $this->byXpath("//div[@id='s2id_oro_workflow_transition_account']/a")->click();
        $this->waitForAjax();
        $this->byXpath("//div[@id='select2-drop']/div/input")->value($account);
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@id='select2-drop']//div[contains(., '{$account}')]",
            "Owner autocoplete doesn't return search value"
        );
        $this->byXpath("//div[@id='select2-drop']//div[contains(., '{$account}')]")->click();

        return $this;
    }

    public function setBudget($budget)
    {
        $this->budget = $this->byId('oro_workflow_transition_budget_amount');
        $this->budget->clear();
        $this->budget->value($budget);
        return $this;
    }

    public function getBudget()
    {
        return $this->byId('oro_workflow_transition_budget_amount')->value();
    }

    public function setProbability($probability)
    {
        $this->probability = $this->byId('oro_workflow_transition_probability');
        $this->probability->clear();
        $this->probability->value($probability);
        return $this;
    }

    public function getProbability()
    {
        return $this->byId('oro_workflow_transition_probability')->value();
    }

    public function setCustomerNeed($customerneed)
    {
        $this->customerNeed = $this->byId('oro_workflow_transition_customer_need');
        $this->customerNeed->clear();
        $this->customerNeed->value($customerneed);
        return $this;
    }

    public function getCustomerNeed()
    {
        return $this->byId('oro_workflow_transition_customer_need')->value();
    }

    public function setSolution($solution)
    {
        $this->solution = $this->byId('oro_workflow_transition_proposed_solution');
        $this->solution->clear();
        $this->solution->value($solution);
        return $this;
    }

    public function getSolution()
    {
        return $this->solution->value();
    }

    public function setCloseRevenue($closerevenue)
    {
        $this->closerevenue = $this->byId('oro_workflow_transition_close_revenue');
        $this->closerevenue->clear();
        $this->closerevenue->value($closerevenue);
        return $this;
    }

    public function setCloseReason($closereason)
    {
        $this->closerevenue = $this->select($this->byId('oro_workflow_transition_close_reason_name'));
        $this->closerevenue->selectOptionByLabel($closereason);
        return $this;
    }

    public function setCloseDate($closedate)
    {
        $this->closedate = $this->byId($this->byId('date_selector_oro_workflow_transition_close_date'));
        $this->closedate->clear();
        $this->closedate->value($closedate);
        return $this;
    }

    public function setCompanyName($company)
    {
        $this->closedate = $this->byId('oro_workflow_transition_company_name');
        $this->closedate->clear();
        $this->closedate->value($company);
        return $this;
    }

    public function getCompanyName()
    {
        return $this->byId('oro_workflow_transition_company_name')->value();
    }

    public function qualify()
    {
        $this->byXPath("//div[@class='btn-group']/a[@id='transition-sales_lead-qualify']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-content ui-widget-content']/preceding-sibling::div/span[text()='Qualify']"
        );
        return $this;
    }

    public function disqualify()
    {
        $this->byXPath("//div[@class='btn-group']/a[@id='transition-sales_lead-cancel']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function develop()
    {
        $this->byXpath("//div[@class='btn-group']/a[@id='transition-sales_flow_b2b-develop']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->assertElementPresent(
            "//div[@class='ui-dialog-content ui-widget-content']/preceding-sibling::div/span[text()='Develop']"
        );
        return $this;
    }

    public function closeAsWon()
    {
        $this->byXpath("//div[@class='btn-group']/a[@id='transition-sales_flow_b2b-close_as_won']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function closeAsLost()
    {
        $this->byXpath("//div[@class='btn-group']/a[@id='transition-sales_flow_b2b-close_as_lost']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function submit()
    {
        $this->byXpath("//button[normalize-space(text()) = 'Submit']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }
}
