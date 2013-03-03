<?php

  require_once 'config.php';
  require_once 'models/Model.php';

  /**
   * Defines interactions between users and colleges and rooms
   * A room can only be allocated to one person and one person can only be allocated to one room
   */
  class Allocation_Model extends Model {

    public function __construct ($table = TABLE_ALLOCATIONS) {
      parent::__construct($table);
    }

    public function get_rooms_from_college ($college, array $rooms) {
      return $this->select('room', "WHERE college='$college' AND room IN ('".implode("', '", $rooms)."')");
    }

    public function get_multiple_rooms (array $eids) {
      return $this->select("room", "WHERE eid IN ('".implode("', '", $eids)."')");
    }


    public function get_rooms_from_college ($colleg) {
      return Model::to_array($this->select("*", "WHERE college='$college' AND room IS NOT NULL"));
    }

    public function get_allocation ($eid) {
      $result = Model::to_array($this->select('*', "WHERE eid='$eid'"));
      if ($result === Model::SQL_FAILED) {
        return Model::SQL_FAILED;
      }
      if (count($result) === 0) {
        return null;
      }
      return $result[0];
    }

    public function update_allocation ($eid, $columns) {
      return $this->update($columns, "WHERE eid='$eid'");
    }


  }

?>