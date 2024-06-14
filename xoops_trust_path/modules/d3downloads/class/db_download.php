<?php

// for DB_insert , DB_update , DB_delete etc.

if( ! class_exists( 'db_download' ) )
{
	class db_download
	{
		public $table_name;
		public $id_name;
		public $db;

		public function db_download( $table_name, $id_name )
		{
			$this->db =& Database::getInstance();
			$this->table = $table_name;
			$this->id = $id_name;
		}

		public function db_insert( $set4sql )
		{
			$sql = "INSERT INTO ".$this->table." SET $set4sql";
			$result = $this->db->query( $sql );
			$new_id = $this->db->getInsertId();
			return $new_id;
		}

		public function db_update( $set4sql, $id )
		{
			$sql = "UPDATE ".$this->table." SET $set4sql WHERE ".$this->id."='".$id."'";
			$result = $this->db->query( $sql );
			return $result;
		}

		public function db_delete( $id )
		{
			$sql = "DELETE FROM ".$this->table." WHERE ".$this->id."='".$id."'";
			$result = $this->db->query( $sql );
			return $result;
		}

		public function db_getrowsnum( $id )
		{
			$sql = "SELECT * FROM ".$this->table." WHERE ".$this->id."='".$id."'";
			$result = $this->db->query( $sql );
			return $this->db->getRowsNum( $result );
		}
	}
}

?>