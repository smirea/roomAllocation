<?php

	require_once 'config.php';
	require_once 'models/Model.php';

  /**
   * Describes the roommate request between two user consisting out of the eid of the two user and a request id.
   */
	class Request_Model extends Model {

		public function __construct ($table = TABLE_REQUESTS) {
			parent::__construct($table);
		}

		public function send_request ($eid_from, $eid_to) {
			return $this->insert(array("eid_from" => $eid_from, "eid_to" => $eid_to));
		}

		public function request_exists ($eid, $eid_to) {
			$requests = $this->select('id', "WHERE (eid_from='$eid' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid')");
			if($requests){
				return (mysql_num_rows($requests) > 0);
			} else {
				return Model::SQL_FAILED;
			}
		}

		public function is_request ($eid_from, $eid_to) {
			$request = $this->select('id', "WHERE eid_from='$eid_from' AND eid_to='$eid_to'");
			if ($request) {
				return (mysql_num_rows($request) > 0);
			} else {
				return Model::SQL_FAILED;
			}
		}

		public function accept_request ($eid_from, $eid_to) {
			return $this->delete("WHERE (eid_from='$eid_from' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid_from')");
		}

		public function remove_remaining ($eid) {
			return $this->delete("WHERE eid_to='$eid'");
		}

	}

?>