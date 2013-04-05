<?php

  require_once 'config.php';
  require_once 'models/Model.php';

  /**
   * Defines interactions between users and the groups they are in
   * Includes both Groups and InGroup Tables
   */
  class Group_Model extends Model {

    public $In_Group_Model;

    public function __construct ($table = TABLE_GROUPS, $in_group_table = TABLE_IN_GROUP) {
      parent::__construct($table);
      $this->In_Group_Model = new Model($in_group_table);
    }

    public function delete_groups ($group_ids) {
      $gids = "'".implode(', ', $group_ids)."'";
      $this->delete("WHERE id IN ($gids)");
      $this->In_Group_Model->delete("WHERE group_id IN ($gids)");
    }

    public function set_group_score ($group_id, $points) {
      $this->update("score='$points'", "WHERE id='$group_id'");
    }

    public function add_to_group ($eid, $group_id) {
      if( $group_id === null ){
        $this->insert(array('score' => 0));
        $group_id = mysql_insert_id();
      }
      $this->In_Group_Model->insert(array(
        'eid' => $eid,
        'group_id' => $group_id
      ));

      return $group_id;
    }

    public function remove_from_group ($eid = null, $group_id = null) {
      $conditions = array();
      if ($eid !== null) {
        $conditions[] = "eid='$eid'";
      }
      if ($group_id !== null) {
        $conditions[] = "group_id='$group_id'";
      }
      return $this->In_Group_Model->delete("WHERE ".implode(' AND ', $conditions));
    }

    public function get_group_id ($eid) {
      $result = Model::get_first_row($this->In_Group_Model->select("group_id", "WHERE eid='$eid'"));
      if (!$result || !count($result) === 0) {
        return Model::SQL_FAILED;
      }
      return $result['group_id'];
    }

    public function get_group_members_eid ($eid) {
      $group_id = $this->get_group_id($eid);
      return $this->to_array(
        $this->In_Group_Model->select('eid', "WHERE group_id='$group_id'"),
        'eid'
      );
    }

  }
?>