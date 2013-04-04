<?php
/***************************************************************************\
    This file is part of RoomAllocation.

    RoomAllocation is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    RoomAllocation is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with RoomAllocation.  If not, see <http://www.gnu.org/licenses/>.
\***************************************************************************/
?>
<?php

  /** Admin config */
  define( 'ADMIN_ACCOUNTS', 'smirea,dkundel,rnjenga,mdiasdasil' );
  $admin = explode(',', ADMIN_ACCOUNTS);

  /** Bulk config */
  define( 'DYNAMIC_CONFIG_FILE', '__config_d.php' );

  define( 'STATUS_UNDERGRAD',   'undergrad' );
  define( 'STATUS_MASTER',      'master' );
  define( 'STATUS_PHD',         'phd' );
  define( 'STATUS_FOUNDATION',  'foundation-year' );

  define( 'FRESHMAN_EID', 0 );

  /** Database config */
  define( 'DB_USER', 'jPerson' );
  define( 'DB_PASS', 'jacobsRulz' );
  define( 'DB_NAME', 'RoomAllocation' );

  define( 'TABLE_ALLOCATIONS',        'Allocations' );
  define( 'TABLE_APARTMENT_CHOICES',  'Apartment_Choices' );
  define( 'TABLE_COLLEGE_CHOICES',    'College_Choices' );
  define( 'TABLE_PEOPLE',             'People' );
  define( 'TABLE_REQUESTS',           'Requests' );
  define( 'TABLE_GROUPS',             'Groups' );
  define( 'TABLE_IN_GROUP',           'InGroup' );

  dbConnect( DB_USER, DB_PASS, DB_NAME );

  session_start();

  /******************
  ***** INCLUDES ****
  ******************/

  if( !file_exists( DYNAMIC_CONFIG_FILE ) ){
    file_put_contents( DYNAMIC_CONFIG_FILE, '<?php /** Needs to be generated **/ ?>' );
    C('DEBUG',                     0);  // mainly disable passwords for all accounts
    C('round.active',              0);  // if students can perform any action in terms of allocation (not choosing)
    C('round.number',              1);  // the current round number. useful for logging
    C('round.restrictions',        0);  // whether to use restrictions or not (i.e. config_allowed.php)
    C('round.type',                0);
    C('roommates.min',             1);
    C('roommates.max',             1);
    C('roommates.freshman',        0);  // whether to allow rooming with a freshman
    C('apartment.choices',         9);
    C('points.min',                1);
    C('points.max',                7);
    C('points.cap',                7);  // points < points.cap ? points : points.cap
    C('allocation.allocateRandom', 0);
    C('allocation.nationalityCap', 8);  // max number of people of the same nationality on a floor (hardcoded to 40%)
    C('disabled.Mercator'        , '');  // custom disabled rooms (like reserver for CMs , etc)
    C('disabled.Krupp'           , '');  // custom disabled rooms (like reserver for CMs , etc)
    C('disabled.College-III'     , '');  // custom disabled rooms (like reserver for CMs , etc)
    C('disabled.Nordmetall'      , '');  // custom disabled rooms (like reserver for CMs , etc)
    C('college.limit.College-III', 240);
    C('college.limit.Mercator'   , 188);
    C('college.limit.Krupp'      , 188);
    C('college.limit.Nordmetall' , 240);
    C('college.limit.threshold'  , 0.8);
    C('message.info'             , '');   // display a global info message to all users
    C('message.warning'          , '');   // display a global warning message to all users
    C('message.error'            , '');   // display a global error message to all users

  }
  require_once( DYNAMIC_CONFIG_FILE );
  require_once( 'config_allowed.php' );

  /******************
  ***** GENERAL *****
  ******************/

  define( 'DEBUG', C('DEBUG') );

  /** General config */
  define( 'MAX_ROOMMATES',    C('roommates.max') );
  define( 'MIN_ROOMMATES',    C('roommates.min') );
  define( 'MAX_ROOM_CHOICES', C('apartment.choices') );
  define( 'MIN_POINTS',       C('points.min') );
  define( 'MAX_POINTS',       C('points.max') );

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
   *
   * @todo make this multi-level (just like in vanilla forums)
   */
  function C( $key, $value = null ){
    global $configuration;
    if( !$key ){
      return null;
    } else if( $value !== null ){
      $v = $value;
      $configuration[$key] = $v;
      file_put_contents( DYNAMIC_CONFIG_FILE, '<?php
/**
 * Dynamically generated config file
 * Check config.php for more information
 * last edited on '.date('d.m.Y').' at '.date('h:i:s').'
 */

'.serialize_array( $configuration, 'configuration' ).'

/** ************************************ **/
?>');
    } else {
      return isset($configuration[$key]) ? $configuration[ $key ] : null;
    }
  }

  /**
   * Creates the string representation of the array so it can be included afterwards
   * @param {array} $array
   * @param {string} $name
   * @todo make this recursive
   * @return {string}
   */
  function serialize_array( array $array, $name ){
    $h = array();
    $h[] = "\$$name = array();";
    foreach( $array as $key => $value ){
      if( !is_numeric( $key ) ) $key = "'$key'";
      if( is_string( $value ) ){
        $value = "'$value'";
      } else if( is_bool( $value ) ){
        $value = $value ? 'true' : 'false';
      }
      $h[] = '$'.$name."[$key] = $value;";
    }
    return implode("\n", $h);
  }

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

  /**
   * @brief Wraps var_export into a <pre></pre> tag for nice formatting
   * @param {mixed} [$arg_n]
   */
  function v_export(){
    $args = func_get_args();
    echo '<pre>';
    foreach( $args as $arg ){
      var_export( $arg );
      echo "\n";
    }
    echo '</pre>';
  }
?>
