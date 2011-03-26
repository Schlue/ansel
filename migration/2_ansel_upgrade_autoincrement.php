<?php
/**
 * Upgrade for autoincrement
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class AnselUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('ansel_images', 'image_id', 'autoincrementKey');
        try {
            $this->dropTable('ansel_images_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('ansel_faces', 'face_id', 'autoincrementKey');
        try {
            $this->dropTable('ansel_faces_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('ansel_shares', 'share_id', 'autoincrementKey');
        try {
            $this->dropTable('ansel_shares_seq');
        } catch (Horde_Db_Exception $e) {
        }
        $this->changeColumn('ansel_tags', 'tag_id', 'autoincrementKey');
        try {
            $this->dropTable('ansel_tags_seq');
        } catch (Horde_Db_Exception $e) {
        }
    }

    public function down()
    {
        $tableList = $this->tables();

        $this->changeColumn('ansel_images', 'image_id', 'integer', array('null' => false, 'autoincrement' => false));
        $this->changeColumn('ansel_faces', 'face_id', 'integer', array('null' => false, 'autoincrement' => false));
        $this->changeColumn('ansel_shares', 'share_id', 'integer', array('null' => false, 'autoincrement' => false));

        if (in_array('ansel_tags', $tableList)) {
            $this->changeColumn('ansel_tags', 'tag_id', 'integer', array('null' => false, 'autoincrement' => false));
        }
    }

}
