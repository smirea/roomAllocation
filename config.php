<?php
  
  define( 'DEBUG', true );
  
  /** General config */
  define( 'MAX_ROOMMATES', 1 );
  define( 'MAX_ROOM_CHOICES', 9 );
  
  /** Database config */
  define( 'DB_USER', 'jPerson' );
  define( 'DB_PASS', 'jacobsRulz' );
  define( 'DB_NAME', 'RoomAllocation' );
  
  define( 'TABLE_ALLOCATIONS', 'Allocations' );
  define( 'TABLE_APARTMENT_CHOICES', 'Apartment_Choices' );
  define( 'TABLE_PEOPLE', 'People' );
  define( 'TABLE_REQUESTS', 'Requests' );
  define( 'TABLE_GROUPS', 'Groups' );
  define( 'TABLE_IN_GROUP', 'InGroup' );

  dbConnect( DB_USER, DB_PASS, DB_NAME );
  
  session_start();
  
  /******************
  ******* URLS ******
  ******************/
  
  function imageURL( $eid ){
    return "http://swebtst01.public.jacobs-university.de/jPeople/image.php?id=$eid";
//    return "http://localhost/jPeople/images/faces/$eid.png";
  }

  function flagURL( $country ){
    $country = str_replace( " ", '%20', $country );
    return "http://swebtst01.public.jacobs-university.de/jPeople//embed_assets/flags/$country.png";
  }

  /******************
  ****** HELPER  ****
  ******************/

  /**
   * @brief check if an array is associative or not
   * @param {array} $array
   * @return {bool} the result as a boolean 
   */
  function is_assoc(array $array){
    if( !is_numeric( array_shift( array_keys( $array ) ) ) ){
        return true;
    }
    return false;
  }
  
  /**
   * @brief Outputs a JSON with the proper headers from the given array
   * @warning This function terminates the execution (runs exit())
   * @param {array} $arr the given array
   */
  function jsonOutput( array $arr ){
    if( !headers_sent() ){
      header('Cache-Control: no-cache, must-revalidate');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Content-type:application/json');
      header('Content-attributes: application/json; charset=ISO-8859-15');
    }
    exit( json_encode( $arr ) );
  }
  
  /**
   * @brief Outputs an error JSON with the format {"error" => $message}
   * @warning This function terminates the execution (runs exit())
   * @param {string} $message the message to output
   */
  function outputError( $message ){
    jsonOutput( array( 'error' => $message ) );
  }
  
  /**
   * @brief Check if condition is false, in which case run outputError( of_the_message )
   * @warning This function the execution (runs exit())
   * @param {bool} $bool
   * @param {string} $message what to output if the first param is false
   */
  function e_assert( $bool, $message = "Assertion failed" ){
    if( !$bool ){
      outputError( $message );
    }
  }
  
  /**
   * @brief For each key in keys, apply e_assert( isset($arr[$key]) );
   * @param {array} $arr the array to check into
   * @param {string|array} $keys either a associative array with 'keys_to_check'=>'message_to_output'
   *                              or a comma-separated string of keys (in which case a default message will be used )
   */
  function e_assert_isset( array $arr, $keys ){
    if( is_string( $keys ) ){
      $keys = array_map( 'trim', explode(',',$keys) );
    }
    if( !is_assoc( $keys ) ){
      $keys = array_flip( $keys );
      array_walk( $keys, function(&$item, $key){ $item = "$key not set!"; });
    }
    foreach( $keys as $k => $v ){
      e_assert( isset( $arr[$k] ), $v );
    }
  }
  
  function sqlToJsonOutput( $q ){
    if( $q ){
      jsonOutput( sqlToArray( $q ) );
    } else {
      outputError( mysql_error() );
    }
  }
  
  /**
   * @brief Takes a mysql resource and returns a list of associative arrays 
   *          with the results (one for each row)
   * @param {MySQL} $sql the resource to use
   * @param {} $key
   * @return a list of associative arrays with the result
   */
  function sqlToArray( $sql, $key = null ){
    if( $sql ){
      $a = array();
      while( $r = mysql_fetch_assoc( $sql ) ){
        if( $key ){
          $a[ $r[ $key ] ] = $r;
        } else {
          $a[] = $r;
        }
      }
      return $a;
    } else {
      return array();
    }
  }
  
  /**
   * @brief Perform a database connection
   * @warning Dies if it is unable to make a connection
   * @param {string} $user
   * @param {string} $pass
   * @param {string} $name
   * @param {string} $host
   */
  function dbConnect($user, $pass, $name = null, $host = 'localhost'){
    $connexion = mysql_connect( $host, $user, $pass ) or die ("Could not connect to Data Base!");
    if( $name ) mysql_select_db( $name, $connexion ) or die ("Failed to select Data Base");
  }
  
?>
