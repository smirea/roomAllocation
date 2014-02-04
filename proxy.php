<?php

  /**
   * Resolve all include errors by including all specified files at the root
   */

  require_once 'config.php';
  require_once 'utils.php';

  if (isset($_GET['q']) && strpos($_GET['q'], '../' . DS) === false) {
    /* Avoid NULL byte poisoning for servers running PHP < 5.3.4 */
    $cleanedPath = dirname(__FILE__) . '/' . str_replace(chr(0), '', $_GET['q']);
    if (file_exists($cleanedPath)) {
      require_once $cleanedPath;
    }
  }

?>
