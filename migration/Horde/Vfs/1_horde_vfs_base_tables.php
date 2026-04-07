<?php

class HordeVfsBaseTables extends Horde_Db_Migration_Base
{
    public function up()
    {
        if (!in_array('horde_vfs', $this->tables())) {
            $t = $this->createTable('horde_vfs', ['autoincrementKey' => ['vfs_id']]);
            $t->column('vfs_id', 'int', ['null' => false, 'unsigned' => true]);
            $t->column('vfs_type', 'smallint', ['null' => false, 'unsigned' => true]);
            $t->column('vfs_path', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_name', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_modified', 'bigint', ['null' => false]);
            $t->column('vfs_owner', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_data', 'binary');
            $t->end();
            $this->addIndex('horde_vfs', ['vfs_path']);
            $this->addIndex('horde_vfs', ['vfs_name']);
        }
        if (!in_array('horde_muvfs', $this->tables())) {
            $t = $this->createTable('horde_muvfs', ['autoincrementKey' => ['vfs_id']]);
            $t->column('vfs_id', 'int', ['null' => false, 'unsigned' => true]);
            $t->column('vfs_type', 'smallint', ['null' => false, 'unsigned' => true]);
            $t->column('vfs_path', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_name', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_modified', 'bigint', ['null' => false]);
            $t->column('vfs_owner', 'string', ['limit' => 255, 'null' => false]);
            $t->column('vfs_perms', 'smallint', ['null' => false, 'unsigned' => true]);
            $t->column('vfs_data', 'binary');
            $t->end();
            $this->addIndex('horde_muvfs', ['vfs_path']);
            $this->addIndex('horde_muvfs', ['vfs_name']);
        }
    }

    public function down()
    {
        try {
            $this->dropTable('horde_muvfs');
        } catch (Horde_Exception $e) {
        }

        try {
            $this->dropTable('horde_vfs');
        } catch (Horde_Exception $e) {
        }
    }
}
