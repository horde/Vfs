<?php

class HordeVfsUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('horde_vfs', 'vfs_id', 'autoincrementKey');
        if (in_array('horde_vfs_seq', $this->tables())) {
            $this->dropTable('horde_vfs_seq');
        }
        $this->changeColumn('horde_muvfs', 'vfs_id', 'autoincrementKey');
        if (in_array('horde_muvfs_seq', $this->tables())) {
            $this->dropTable('horde_muvfs_seq');
        }
    }

    public function down()
    {
        try {
            $this->changeColumn('horde_muvfs', 'vfs_id', 'integer', ['null' => false, 'unsigned' => true]);
        } catch (Horde_Db_Exception $e) {
        }

        try {
            $this->changeColumn('horde_vfs', 'vfs_id', 'integer', ['null' => false, 'unsigned' => true]);
        } catch (Horde_Db_Exception $e) {
        }
    }
}
