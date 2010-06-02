<?php

$block_name = _("Recently Added Photos");

/**
 * Display most recently added images.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_recently_added extends Horde_Block {

    var $_app = 'ansel';
    var $_gallery = null;

    function _params()
    {
        $params = array('gallery' => array(
                            'name' => _("Gallery"),
                            'type' => 'enum',
                            'default' => '__random',
                            'values' => array('all' => 'All')),
                        'limit' => array(
                             'name' => _("Maximum number of photos"),
                             'type' => 'int',
                             'default' => 10),
        );

        if ($GLOBALS['ansel_storage']->countGalleries(Horde_Auth::getAuth(), Horde_Perms::READ) < $GLOBALS['conf']['gallery']['listlimit']) {
            foreach ($GLOBALS['ansel_storage']->listGalleries(Horde_Perms::READ) as $id => $gal) {
                if (!$gal->hasPasswd() && $gal->isOldEnough()) {
                    $params['gallery']['values'][$id] = $gal->get('name');
                }
            }
        }

        return $params;
    }

    function _title()
    {
        if ($this->_params['gallery'] != 'all') {
            try {
                $gallery = $this->_getGallery();
            } catch (Exception $e) {
                return Ansel::getUrlFor('view', array('view' => 'List'), true)->link() . _("Gallery") . '</a>';
            }

            // Build the gallery name.
            if (isset($this->_params['gallery'])) {
                $name = @htmlspecialchars($gallery->get('name'), ENT_COMPAT,
                                          Horde_Nls::getCharset());
            }

            $style = $gallery->getStyle();
            $viewurl = Ansel::getUrlFor('view',
                                        array('slug' => $gallery->get('slug'),
                                              'gallery' => $gallery->id,
                                              'view' => 'Gallery'),
                                        true);
        } else {
            $viewurl = Ansel::getUrlFor('view', array('view' => 'List'), true);
            $name = _("All Galleries");
        }
        return sprintf(_("Recently Added Photos From %s"), $viewurl->link() . $name . '</a>');
    }

    function _content()
    {
        if ($this->_params['gallery'] == 'all') {
            $galleries = array();
        } elseif (!is_array($this->_params['gallery'])) {
            $galleries = array($this->_params['gallery']);
        } else {
            $galleries = $this->_params['gallery'];
        }

        // Retrieve the images, but protect against very large values for
        // limit.
        try {
            $results = $GLOBALS['ansel_storage']->getRecentImages(
            $galleries, min($this->_params['limit'], 100));
        } catch (Ansel_Exception $e) {
            return $e->getMessage();
        }
        $preview_url = Horde::applicationUrl('preview.php', true);
        $header = array(_("Date"), _("Photo"), _("Gallery"));

        $html = <<<HEADER

<div id="ansel_preview"></div>
<script type="text/javascript">
function previewImage(e, image_id) {
    $('ansel_preview').style.left = Event.pointerX(e) + 'px';
    $('ansel_preview').style.top = Event.pointerY(e) + 'px';
    new Ajax.Updater({success:'ansel_preview'},
                     '$preview_url',
                     {method: 'post',
                      parameters:'?image=' + image_id,
                      onsuccess:$('ansel_preview').show()});
}
</script>
<table class="linedRow" cellspacing="0" style="width:100%">
 <thead><tr class="item nowrap">
  <th class="item leftAlign">$header[0]</th>
  <th class="item leftAlign">$header[1]</th>
  <th class="item leftAlign">$header[2]</th>
</tr></thead>
<tbody>
HEADER;

        foreach ($results as $image) {
            $gallery = $GLOBALS['ansel_storage']->getGallery($image->gallery);

            // Don't show locked galleries in the block.
            if (!$gallery->isOldEnough() || $gallery->hasPasswd()) {
                continue;
            }
            $style = $gallery->getStyle();

            $galleryLink = Ansel::getUrlFor(
                'view', array('slug' => $gallery->get('slug'),
                              'gallery' => $gallery->id,
                              'view' => 'Gallery'),
                true);
            $galleryLink = $galleryLink->link()
                . @htmlspecialchars($gallery->get('name'), ENT_COMPAT,
                                    Horde_Nls::getCharset())
                . '</a>';

            $caption = substr($image->caption, 0, 30);
            if (strlen($image->caption) > 30) {
                $caption .= '...';
            }

            /* Generate the image view url */
            $url = Ansel::getUrlFor(
                'view',
                array('view' => 'Image',
                      'slug' => $gallery->get('slug'),
                      'gallery' => $gallery->id,
                      'image' => $image->id,
                      'gallery_view' => $style['gallery_view']));
            $html .= '<tr><td>' . strftime('%x', $image->uploaded)
                . '</td><td class="nowrap">'
                . $url->link(
                    array('onmouseout' => '$("ansel_preview").hide();$("ansel_preview").update("");',
                          'onmouseover' => 'previewImage(event, ' . $image->id . ');'))
                . @htmlspecialchars(
                    strlen($caption) ? $caption : $image->filename,
                    ENT_COMPAT, Horde_Nls::getCharset())
                . '</a></td><td class="nowrap">' . $galleryLink . '</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    function _getGallery()
    {
        /* Make sure we haven't already selected a gallery. */
        if ($this->_gallery instanceof Ansel_Gallery) {
            return $this->_gallery;
        }

        /* Get the gallery object and cache it. */
        if (isset($this->_params['gallery']) &&
            $this->_params['gallery'] != '__random') {
            $this->_gallery = $GLOBALS['ansel_storage']->getGallery($this->_params['gallery']);
        } else {
            $this->_gallery = $GLOBALS['ansel_storage']->getRandomGallery();
        }

        if (empty($this->_gallery)) {
            throw new Horde_Exception_NotFound(_("Gallery not found."));
        } elseif (!$this->_gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::READ)) {
            throw new Horde_Exception_PermissionDenied(_("Access denied viewing this gallery."));
        }

        /* Return a reference to the gallery. */
        return $this->_gallery;
    }

}
