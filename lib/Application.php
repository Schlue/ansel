<?php
/**
 * Ansel application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Ansel through this API.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

if (!defined('ANSEL_BASE')) {
    define('ANSEL_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(ANSEL_BASE . '/config/horde.local.php')) {
        include ANSEL_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', ANSEL_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Ansel_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $ansel_db - TODO
     *   $ansel_storage - TODO
     *
     * @throws Horde_Exception
     */
    protected function _init()
    {
        if (!$GLOBALS['conf']['image']['driver']) {
            throw new Horde_Exception('You must configure a Horde_Image driver to use Ansel');
        }

        $binders = array(
            'Ansel_Storage' => new Ansel_Injector_Binder_Storage(),
            'Ansel_Styles' => new Ansel_Injector_Binder_Styles(),
        );
        foreach ($binders as $interface => $binder) {
            $GLOBALS['injector']->addBinder($interface, $binder);
        }

        // Create db, share, and vfs instances.
        $GLOBALS['ansel_db'] = Ansel::getDb();
        $GLOBALS['ansel_storage'] = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope('ansel');

        /* Set a logger for the Vfs */
        $GLOBALS['injector']->getInstance('Horde_Vfs')->getVfs('images')->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array();
        $perms['tree']['ansel']['admin'] = false;
        $perms['title']['ansel:admin'] = _("Administrators");

        return $perms;
    }

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        global $conf, $prefs;

        switch ($ui->group) {
        case 'metadata':
            if (!$prefs->isLocked('exif_tags')) {
                $fields = Horde_Image_Exif::getFields(array($conf['exif']['driver'], !empty($conf['exif']['params']) ? $conf['exif']['params'] : array()), true);
                $ui->override['exif_tags'] = $fields;
                $ui->override['exif_title'] = array_merge(array(
                    'none' => _("None")
                ), $fields);
            }
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the options page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'default_category_select':
            $cManager = new Horde_Prefs_CategoryManager();
            return _("Default category for galleries") . $cManager->getSelect('default_category_select', $GLOBALS['prefs']->getValue('default_category'));

        case 'default_gallerystyle_select':
            return _("Default style for galleries") .
                Ansel::getStyleSelect('default_gallerystyle_select', $GLOBALS['prefs']->getValue('default_gallerystyle')) .
                '<br />';
        }

        return '';
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'default_category_select':
            if (isset($ui->vars->default_category_select)) {
                $GLOBALS['prefs']->setValue('default_category', $ui->vars->default_category_select);
                return true;
            }
            break;

        case 'default_gallerystyle_select':
            if (isset($ui->vars->default_gallerystyle_select)) {
                $GLOBALS['prefs']->setValue('default_gallerystyle', $ui->vars->default_gallerystyle_select);
                return true;
            }
            break;
        }

        return false;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Ansel::getMenu();
    }

}
