<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Site;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Sites Module
 */
class SiteModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function editExistingRecord(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $acceptanceUrl = $I->grabModuleConfig('WebDriver', 'url');
        $acceptanceUrlWithTrailingSlash = rtrim($acceptanceUrl, '/') . '/';

        $I->amGoingTo('Access the site module');
        $I->click('Sites');
        $I->switchToContentFrame();
        $I->canSee('Site Configuration', 'h1');

        $I->amGoingTo('edit an automatically created site configuration');
        $I->click('Edit');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit Site Configuration', 'h1');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[identifier]")]', 'autogenerated-1-c4ca4238a0');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[base]")]', $acceptanceUrlWithTrailingSlash);

        $I->amGoingTo('Edit the default site language');
        $I->click('Languages');
        $I->canSee('English [0] (en_US.UTF-8)');
        $I->click('div[data-table-unique-original-value=site_language_0] > div:nth-child(1) > div:nth-child(1)');
        $I->waitForElementVisible('div[data-table-unique-original-value=site_language_0] > div.panel-collapse');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'English Edit');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', $acceptanceUrlWithTrailingSlash);
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'en_US.UTF-8');

        $I->amGoingTo('Delete a site language');
        $I->canSee('styleguide demo language danish [1] (da_DK.UTF-8)');
        $I->click('div[data-table-unique-original-value=site_language_1] > div:nth-child(1) > div:nth-child(1) > div:nth-child(3) button');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->dontSee('styleguide demo language danish [1] (da_DK.UTF-8)');
        $I->canSee('styleguide demo language danish [1]', 'option');

        $I->amGoingTo('Save the site configuration');
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->amGoingTo('Verify default site language has changed and danish is deleted');
        $I->canSee('English Edit [0] (en_US.UTF-8)');
        $I->dontSee('styleguide demo language danish [1] (da_DK.UTF-8)');

        $I->amGoingTo('Create a completely new site language');
        $I->click('Create new language');
        $I->waitForElementVisible('div.inlineIsNewRecord');
        $I->scrollTo('div.inlineIsNewRecord');
        $I->canSee('[New language]');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'New Language');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', '/new-language/');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'C');
        $I->selectOption('//select[contains(@name, "[site_language]") and contains(@name, "[iso-639-1]")]', 'hr');

        $I->amGoingTo('Save the site configuration');
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->amGoingTo('Verify new site configuration has been added with the next available language ID)');
        $I->canSee('New Language [9] (C)');

        $I->amGoingTo('Close the site configuration form');
        $I->click('Close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Site Configuration', 'h1');
        $I->canSee('autogenerated-1-c4ca4238a0', 'code');

        $I->amGoingTo('Ensure the previously added language is available and the default is prefilled in a new site configuration');
        $I->click('Add new site configuration for this site');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Create new Site Configuration', 'h1');
        $I->click('Languages');
        $I->canSee('New Language [9]', 'option');
        $I->canSee('English Edit [0] (en_US.UTF-8)');
        $title = $I->grabValueFrom('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]');
        $I->assertEquals('English Edit', $title);
        $I->click('div.inlineIsNewRecord:nth-child(1) > div:nth-child(1) > div:nth-child(1)');

        $I->amGoingTo('Verify new language can be added from selector box and deleted afterwards');
        $I->selectOption('.t3js-create-new-selector', '9');
        $I->waitForElementVisible('div.inlineIsNewRecord:nth-child(2)');
        $I->scrollTo('div.inlineIsNewRecord:nth-child(2)');
        $I->canSee('New Language [9] (C)');
        $I->click('div.inlineIsNewRecord:nth-child(2) > div:nth-child(1) > div:nth-child(1)');
        $I->canSee('/hr/');
        $I->click('div.inlineIsNewRecord:nth-child(2) > div:nth-child(1) > div:nth-child(1) > div:nth-child(3) button');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->see('New Language [9]', 'option');

        $I->amGoingTo('Undo the generation of the new site configuration');
        $I->click('Close');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->canSee('Add new site configuration for this site', 'a');
    }

    /**
     * Add a default FE ts snipped to the existing site config and verify FE is rendered
     *
     * @depends editExistingRecord
     */
    public function defaultFrontendRendering(ApplicationTester $I, PageTree $pageTree, ModalDialog $modalDialog): void
    {
        $I->amGoingTo('Create a default FE typoscript for the created site configuration');

        // Select the root page
        $I->switchToMainFrame();
        $I->amGoingTo('Access template module');
        $I->click('Template');
        // click on PID=0
        $I->waitForElement('svg .nodes .node');
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');
        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('Template tools');

        $I->amGoingTo('Select the root page and switch back to content frame');
        $I->switchToMainFrame();
        $I->click('Template');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->waitForText('Create new website');

        $I->amGoingTo('Create a new template for the root page');
        $I->click("//input[@name='newWebsite']");
        $I->selectOption('.t3-js-jumpMenuBox', 'Info/Modify');
        $I->see('NEW SITE', 'h3');
        $I->waitForElement('table.table.table-striped');
        $I->see('Title');

        $I->amGoingTo('Add the PAGE object');
        $I->click('Edit the whole template record');
        $I->waitForElement('#EditDocumentController');
        $I->fillField($this->getInputByLabel($I, 'Template Title'), 'Default Title');
        $I->click("//button[@name='_savedok']");
        $I->waitForElementNotVisible('#t3js-ui-block', 30);
        $I->waitForElement('#EditDocumentController');
        $I->waitForElementNotVisible('#t3js-ui-block');

        // watch out for new line after each instruction. Anything else doesn't work.
        $config = 'page = PAGE
page.shortcutIcon = fileadmin/styleguide/bus_lane.jpg
page.10 = TEXT
page.10.value = This is a default text for default rendering without dynamic content creation
';
        $I->fillField($this->getInputByLabel($I, 'Setup', 'textarea'), $config);
        $I->click('//button[@name="_savedok"]');
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->amGoingTo('Call FE and verify it is properly rendered');
        $I->amOnPage('/');
        $I->canSee('This is a default text for default rendering without dynamic content creation');

        $I->amGoingTo('Delete the site template record again');
        $I->amOnPage('/typo3/index.php');
        $I->click('Template');
        // click on PID=0
        $I->waitForElement('svg .nodes .node');
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');
        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->switchToMainFrame();
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->click('Edit the whole template record');
        $I->waitForElement('#EditDocumentController');
        $I->click('Delete');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->see('Create new website');
    }

    /**
     * @depends defaultFrontendRendering
     * @throws \Exception
     */
    public function createSiteConfigIfNoneExists(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $acceptanceUrl = $I->grabModuleConfig('WebDriver', 'url');
        $acceptanceUrlWithTrailingSlash = rtrim($acceptanceUrl, '/') . '/';

        $I->amGoingTo('Access the site module');
        $I->click('Sites');
        $I->switchToContentFrame();
        $I->canSee('Site Configuration', 'h1');

        $I->amGoingTo('delete the auto generated config in order to create one manually');
        $I->click('Delete site configuration');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Delete');
        $I->switchToContentFrame();

        $I->amGoingTo('manually create a new site config for the existing root page');
        $I->click('Add new site configuration for this site');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Create new Site configuration');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[identifier]")]', 'SitesTestIdentifier');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[base]")]', $acceptanceUrlWithTrailingSlash);
        $I->click('Languages');

        $I->amGoingTo('Delete the automatically added default language and add it again from the selector afterwards');
        $I->click('div.inlineIsNewRecord > div:nth-child(1) > div:nth-child(1) > div:nth-child(3) button');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->canSee('English [0]', 'option');
        $I->selectOption('.t3js-create-new-selector', '0');
        $I->waitForElementVisible('div.inlineIsNewRecord:nth-child(1)');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'Homepage');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', $acceptanceUrlWithTrailingSlash);
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'en_US.UTF-8');
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);

        $I->amGoingTo('Create and delete new site language. Verify "Placehoder" is not added to selector');
        $I->click('Languages');
        $I->click('Create new language');
        $I->waitForElementVisible('div.inlineIsNewRecord');
        $I->scrollTo('div.inlineIsNewRecord');
        $I->canSee('[New language]');
        $I->click('div.inlineIsNewRecord > div:nth-child(1) > div:nth-child(1) > div:nth-child(3) button');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->dontSee('Placeholder');

        $I->amGoingTo('Close site configuration and verify that it got saved');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->click('div.module-docheader .btn.t3js-editform-close');
        $modalDialog->canSeeDialog();
        $I->click('button[name="save"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Site Configuration', 'h1');
        $I->canSee('SitesTestIdentifier');
    }

    /**
     * Find input field by label name
     */
    protected function getInputByLabel(ApplicationTester $I, string $labelName, string $tag = 'input[@type="text"]'): RemoteWebElement
    {
        $I->comment('Get input for label "' . $labelName . '"');
        return $I->executeInSelenium(
            static function (RemoteWebDriver $webDriver) use ($labelName, $tag) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//label[contains(text(),"' . $labelName . '")]/following-sibling::div//' . $tag
                    )
                );
            }
        );
    }
}
