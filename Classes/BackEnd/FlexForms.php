<?php
namespace OliverKlee\Onetimeaccount\BackEnd;

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

use TYPO3\CMS\Core\Localization\Parser\XliffParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class provides functions for filling the FlexForms.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class FlexForms
{
    /**
     * @var string[]
     */
    protected static $fieldsForRequiring = [
        'company', 'title', 'name', 'first_name', 'last_name', 'address', 'zip', 'city', 'country', 'email', 'www',
        'telephone', 'fax', 'gender', 'static_info_country', 'date_of_birth', 'status', 'comments',
    ];

    /**
     * @var string[]
     */
    protected static $fieldsFromSystemExtensions = [
        'company', 'title', 'name', 'first_name', 'last_name', 'address', 'zip', 'city', 'country', 'email', 'www',
        'telephone', 'fax', 'usergroup',
    ];

    /**
     * @var string[]
     */
    protected static $fieldsFromSrFrontEndUserRegister = [
        'gender', 'zone', 'static_info_country', 'date_of_birth', 'status', 'comments',
    ];

    /**
     * @var string[]
     */
    protected static $fieldsFromSfRegister = ['gender', 'zone', 'static_info_country', 'date_of_birth', 'status', 'comments'];

    /**
     * @var string[]
     */
    protected static $fieldsFromDirectMail = ['module_sys_dmail_newsletter', 'module_sys_dmail_html'];

    /**
     * The labels are static in order to avoid loading the language file multiple times for multiple user functions called from
     * the FlexForms.
     *
     * @var string[]
     */
    protected static $languageLabels = [];

    /**
     * Constructor.
     *
     * @throws \InvalidArgumentException
     * @throws \BadFunctionCallException
     */
    public function __construct()
    {
        $this->loadLanguageLabels();
    }

    /**
     * Sets the selectable items for the fields to display in $configuration.
     *
     * @param string[][][] $configuration
     *
     * @return void
     *
     * @throws \BadFunctionCallException
     */
    public function getFieldsToDisplay(array &$configuration)
    {
        $this->createCheckboxFieldsForKeys($configuration, $this->getAvailableFieldNames());
    }

    /**
     * Sets the selectable items for the fields to require in $configuration.
     *
     * @param string[][][] $configuration
     *
     * @return void
     *
     * @throws \BadFunctionCallException
     */
    public function getFieldsToRequire(array &$configuration)
    {
        $availableFields = array_intersect($this->getAvailableFieldNames(), static::$fieldsForRequiring);
        $this->createCheckboxFieldsForKeys($configuration, $availableFields);
    }

    /**
     * Sets the selectable items for $availableFields in in $configuration.
     *
     * @param string[][][] $configuration
     * @param string[] $availableFields
     *
     * @return void
     */
    protected function createCheckboxFieldsForKeys(array &$configuration, array $availableFields)
    {
        /** @var string[][] $items */
        $items = [];
        foreach ($availableFields as $fieldName) {
            $label = $this->getLanguageLabelForFrontEndUserField($fieldName);
            if ($label === '') {
                $label = $fieldName;
            }
            $items[] = [$label, $fieldName];
        }

        $configuration['items'] = $items;
    }

    /**
     * Returns the names of all relevant FE user fields that are available through installed extensions.
     *
     * @return string[]
     *
     * @throws \BadFunctionCallException
     */
    protected function getAvailableFieldNames()
    {
        $availableFieldNames = static::$fieldsFromSystemExtensions;
        if (ExtensionManagementUtility::isLoaded('sr_feuser_register')) {
            $availableFieldNames = array_merge($availableFieldNames, static::$fieldsFromSrFrontEndUserRegister);
        }
        if (ExtensionManagementUtility::isLoaded('sf_register')) {
            $availableFieldNames = array_merge($availableFieldNames, static::$fieldsFromSfRegister);
        }
        if (ExtensionManagementUtility::isLoaded('direct_mail')) {
            $availableFieldNames = array_merge($availableFieldNames, static::$fieldsFromDirectMail);
        }
        //register additional fieldnames provided by custom extensions
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['onetimeaccount']['additionalFeuserFieldnames']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['onetimeaccount']['additionalFeuserFieldnames'])){
            $availableFieldNames = array_merge($availableFieldNames, $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['onetimeaccount']['additionalFeuserFieldnames']);
        }
        return array_unique($availableFieldNames);
    }

    /**
     * Reads the language labels into self::$languageLabels (if they have not been loaded yet).
     *
     * @return void
     *
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     */
    protected function loadLanguageLabels()
    {
        if (count(self::$languageLabels) > 0) {
            return;
        }

        $languageFilePath = ExtensionManagementUtility::extPath('onetimeaccount') . 'Resources/Private/Language/locallang_db.xlf';
        /** @var XliffParser $xmlParser */
        $xmlParser = GeneralUtility::makeInstance(XliffParser::class);
        self::$languageLabels = $xmlParser->getParsedData($languageFilePath, $this->getLanguageService()->lang);
    }

    /**
     * Finds the language label for $fieldName.
     *
     * @param string $fieldName the field name, e.g. "full_name"
     *
     * @return string
     */
    protected function getLanguageLabelForFrontEndUserField($fieldName)
    {
        $fullKey = 'fe_users.' . $fieldName;

        return $this->getLanguageService()->getLLL($fullKey, self::$languageLabels);
    }

    /**
     * Returns the language service.
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
