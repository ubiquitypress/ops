<?php

/**
 * @file controllers/modals/publish/OPSPublishHandler.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OPSPublishHandler
 * @ingroup controllers_modals_publish
 *
 * @brief A handler to load final publishing confirmation checks
 */

// Import the base Handler.
import('lib.pkp.controllers.modals.publish.PublishHandler');

class OPSPublishHandler extends PublishHandler {

	/**
	 * Constructor.
	 */
	function __construct() {
		$this->addRoleAssignment(
			[ROLE_ID_AUTHOR],
			['publish']
		);
		parent::__construct();
	}
}

