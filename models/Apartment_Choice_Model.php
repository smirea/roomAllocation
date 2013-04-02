<?php

  require_once 'config.php';
  require_once 'models/Model.php';

  /**
   * Describing the apparment choices a group did.
   */
  class Apartment_Choice_Model extends Model {

    public function __construct ($table = TABLE_APARTMENT_CHOICES) {
      parent::__construct($table);
    }

    public function remove_all_choices ($group_id) {
      return $this->delete("WHERE group_id='$group_id'");
    }

    public function get_all_choices ($group_id) {
      return $this->to_array($this->select('*', "WHERE group_id='$group_id'"));
    }

    public function choices_of_college ($college) {
      return $this->to_array($this->select('*', "WHERE college='$college' ORDER BY number,group_id,choice"));
    }
  }

?>