<?php

  require_once 'models/Model.php';

  class College_Choice_Model extends Model {
    private $colleges;

    public function __construct ($table = TABLE_COLLEGE_CHOICES) {
      parent::__construct($table);
      $this->colleges = array('Mercator', 'Krupp', 'College-III', 'Nordmetall');
    }

    public function remove_choices ($eid) {
      return $this->delete("WHERE eid='$eid'");
    }

    public function set_choices (array $data) {
      $this->remove_choices($data['eid']);
      $prefix = 'choice_';
      foreach ($data as $key => $value) {
        if (
          substr($key, 0, strlen($prefix)) == $prefix &&
          array_search($value, $this->colleges) === false
        ) {
          $this->queries[] = '';
          $this->errors[] = 'Invalid college name';
          return null;
        }
      }
      return $this->insert($data);
    }

    public function get_choices ($eid) {
      return Model::get_first_row($this->select('*', "WHERE eid='$eid'"));
    }

    public function get_all_choices ($columns = '*', $query = '') {
      return $this->to_array($this->select($columns, $query));
    }

    public function delete_all ($query = '') {
      return $this->delete($query);
    }

  }
?>