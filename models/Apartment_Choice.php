<?php

  require_once 'config.php';
  require_once 'models/Model.php'

  /**
   * Describing the apparment choices a group did.
   */
  class Apartment_Choice extends Model {

    public function __construct ($table = TABLE_APARTMENT_CHOICES) {
      parent::__construct($table);
    }

    public remove_all_choices ($group_id) {
      return $this->delete("WHERE group_id='$group_id'");
    }

    public get_all_choices ($group_id) {
      return $this->to_array($this->select('*', "WHERE group_id='$group_id'"));
    }

    public choices_of_college ($college) {
      return $this->to_array($this->select('*', "WHERE college='$college' ORDER BY number,group_id,choice"));
    }

?>