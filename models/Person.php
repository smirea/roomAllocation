<?php

  require_once 'config.php';
  require_once 'models/Model.php'

  class Person extends Model {

    public function __construct ($table = TABLE_PEOPLE) {
      parent::__construct($table);
    }


    public get ($eid) {
      return $this->to_array($this->select('*', "WHERE eid='$eid'"));
    }

    public search ($columns, $min_year, $clause) {
      return $this->select($columns, "WHERE (
                                        (status='undergrad' AND year>'$min_year')
                                        OR (status='foundation-year' AND year='$min_year')
                                      )
                                      AND $clause");
      }
  }

?>