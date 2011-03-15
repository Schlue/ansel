<?php
/**
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

/* Load mobile? */
if ($mode == 'smartmobile' || $mode == 'mobile') {
    include ANSEL_BASE . '/mobile.php';
    exit;
}

Ansel::getUrlFor('default_view', array())->redirect();
