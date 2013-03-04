<?php

  class Model {

    const SQL_FAILED = null;

    protected $table;
    public $queries = array();
    public $errors = array();

    /**
     * Model constructor
     * @param {String} $table the name of the table on which the model should be applied
     */
    function __construct ($table) {
      $this->table = $table;
    }

    /**
     * Perform a sql query
     * @param {String} $columns the columns to select
     * @param {String} $query the select query string to run
     * @return {MySQL}
     */
    public function select ($columns, $query = '') {
      $sql = $this->query("SELECT $columns FROM ".$this->get_table()." $query");
      if (!$sql) {
        return Model::SQL_FAILED;
      }
      return $sql;
    }

    /**
     * Perform an insert query
     * @param {String|Array} $query The insert query string to run.
     *                                If an ASSOCIATIVE array is passed, then it's keys will be used as column names and its values as values.
     *                                Passing a normal array will cause an error
     * @return {Bool}
     */
    public function insert ($query) {
      if (is_array($query)) {
        $fields = implode(', ', Model::quotify($query));
        return $this->insert('('.implode(', ', array_keys($query)).') VALUES ('.$fields.')');
      }
      return $this->query("INSERT INTO ".$this->get_table()." $query");
    }

    /**
     * Perform an update query
     * @param {String} $columns the update query string to run
     * @param {String} $query the update query string to run
     * @return {Bool}
     */
    public function update ($columns, $query) {
      return $this->query("UPDATE ".$this->get_table()." SET $columns $query");
    }

    /**
     * If you don't know what you are doing, then don't use this as well
     * @param {String} $query the delete query string to run
     * @return {Bool}
     */
    public function delete ($query) {
      return $this->query("DELETE FROM ".$this->get_table()." $query");
    }

    /**
     * If you ever use this function, I'll strangle you with your balls
     * love Stefan ^.^
     * @return {Bool}
     */
    public function drop () {
      return $this->query("DROP ".$this->get_table());
    }

    /**
     * Describe the structure of the
     * @return {MySQL}
     */
    public function describe () {
      return $this->query("DESCRIBE ".$this->get_table());
    }

    /**
     * Performs a show operation on the database
     * @return [type] [description]
     */
    public function show ($query) {
      return $this->query("SHOW $query");
    }

    /**
     * Generic wrapper for the mysql_query
     * @param {String} $query any generic query
     */
    public function query ($query) {
      $result = mysql_query($query);
      $this->queries[] = $query;
      $this->errors[] = mysql_error();
      return $result;
    }

    public static function get_first_row ($sql) {
      if (!$sql) {
        return Model::SQL_FAILED;
      }
      $result = Model::to_array($sql);
      if ($result === Model::SQL_FAILED) {
        return Model::SQL_FAILED;
      }
      if (count($result) === 0) {
        return null;
      }
      return $result[0];
    }

    /**
     * Takes a mysql resource and returns a list of associative arrays
     *          with the results (one for each row)
     * @param {MySQL} $sql the resource to use
     * @param {String} $key whether to only extract a specific key
     *                        (useful when you select only one column in a query)
     * @return {Array} Return null if the sql is invalid
     */
    public static function to_array ($sql, $key = null) {
      if (!$sql) {
        return Model::SQL_FAILED;
      }
      $result = array();
      while ($row = mysql_fetch_assoc($sql)) {
        if ($key !== null) {
          $result[] = $row[$key];
        } else {
          $result[] = $row;
        }
      }
      return $result;
    }

    /**
     * @return {String}
     */
    public function get_table () {
      return $this->table;
    }

    /**
     * Wraps the values of the array in quotes
     * @param {Array} $array
     * @param {String} $quote
     * @return {Array}
     */
    public static function quotify (array $array, $quote = "'") {
      return array_map(function ($string) use ($quote) {
        return $quote . $string . $quote;
      }, $array);
    }
  }

?>