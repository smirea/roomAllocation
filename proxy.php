<?php

  /**
   * Resolve all include errors by including all specified files at the root
   */

  require_once 'config.php';
  require_once 'utils.php';

  e_assert_isset($_GET, 'q');

  require_once $_GET['q'];

?>