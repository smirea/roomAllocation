<?php

  require_once 'config.php';
  require_once 'models/Model.php';

  /**
   * Defines interactions between users and the groups they are in
   * Includes both Groups and InGroup Tables
   */
  class Group_Model extends Model {

    public $In_Group_Model;

    public function __construct ($table = TABLE_GROUPS, $in_group_table) {
      parent::__construct($table);
      $this->In_Group_Model = new Model($in_group_table);
    }

    public function delete_groups ($group_ids) {
      $gids = "'".implode(', ', $group_ids)."'";
      $this->delete("WHERE id IN ($gids)");
      $this->In_Group_Model->delete("WHERE group_id IN ($gids)");
    }

  }
?>