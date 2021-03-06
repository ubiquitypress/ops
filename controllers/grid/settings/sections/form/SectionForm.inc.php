<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/editing a section
 */

import('lib.pkp.controllers.grid.settings.sections.form.PKPSectionForm');

class SectionForm extends PKPSectionForm {

	/**
	 * Constructor.
	 * @param $request Request
	 * @param $sectionId int optional
	 */
	function __construct($request, $sectionId = null) {
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
		parent::__construct(
			$request,
			'controllers/grid/settings/sections/form/sectionForm.tpl',
			$sectionId
		);

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
		$this->addCheck(new FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
		$this->addCheck(new FormValidator($this, 'path', 'required', 'manager.setup.form.section.pathRequired'));
		$journal = $request->getJournal();
	}

	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$request = Application::get()->getRequest();
		$journal = $request->getJournal();

		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$sectionId = $this->getSectionId();
		if ($sectionId) {
			$section = $sectionDao->getById($sectionId, $journal->getId());
		}

		if (isset($section)) $this->setData([
			'title' => $section->getTitle(null), // Localized
			'abbrev' => $section->getAbbrev(null), // Localized
			'path' => $section->getPath(),
			'description' => $section->getDescription(null), // Localized
			'metaIndexed' => !$section->getMetaIndexed(), // #2066: Inverted
			'abstractsNotRequired' => $section->getAbstractsNotRequired(),
			'identifyType' => $section->getIdentifyType(null), // Localized
			'editorRestriction' => $section->getEditorRestricted(),
			'policy' => $section->getPolicy(null), // Localized
			'wordCount' => $section->getAbstractWordCount(),
			'assignedSubeditors' => Services::get('user')->getIds([
				'contextId' => Application::get()->getRequest()->getContext()->getId(),
				'roleIds' => ROLE_ID_SUB_EDITOR,
				'assignedToSection' => (int) $this->getSectionId(),
			]),
		]);
		else $this->setData([
			'assignedSubeditors' => [],
		]);

		parent::initData();
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('sectionId', $this->getSectionId());

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::readInputData()
	 */
	function readInputData() {
		parent::readInputData();
		$this->readUserVars(array('abbrev', 'path', 'description', 'policy', 'identifyType', 'metaIndexed', 'abstractsNotRequired', 'editorRestriction', 'wordCount'));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		return $sectionDao->getLocaleFieldNames();
	}

	/**
	 * Save section.
	 * @return mixed
	 */
	function execute(...$functionArgs) {
		$sectionDao = DAORegistry::getDAO('SectionDAO'); /* @var $sectionDao SectionDAO */
		$journal = Application::get()->getRequest()->getJournal();

		// Get or create the section object
		if ($this->getSectionId()) {
			$section = $sectionDao->getById($this->getSectionId(), $journal->getId());
		} else {
			import('classes.journal.Section');
			$section = $sectionDao->newDataObject();
			$section->setJournalId($journal->getId());
		}

		// Populate/update the section object from the form
		$section->setTitle($this->getData('title'), null); // Localized
		$section->setAbbrev($this->getData('abbrev'), null); // Localized
		$section->setPath($this->getData('path'));
		$section->setDescription($this->getData('description'), null); // Localized
		$section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
		$section->setAbstractsNotRequired($this->getData('abstractsNotRequired') ? 1 : 0);
		$section->setIdentifyType($this->getData('identifyType'), null); // Localized
		$section->setEditorRestricted($this->getData('editorRestriction') ? 1 : 0);
		$section->setPolicy($this->getData('policy'), null); // Localized
		$section->setAbstractWordCount($this->getData('wordCount'));

		// Insert or update the section in the DB
		if ($this->getSectionId()) {
			$sectionDao->updateObject($section);
		} else {
			$section->setSequence(REALLY_BIG_NUMBER);
			$this->setSectionId($sectionDao->insertObject($section));
			$sectionDao->resequenceSections($journal->getId());
		}

		return parent::execute(...$functionArgs);
	}
}
