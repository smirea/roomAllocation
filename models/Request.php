<?php

	require_once 'config.php';
	require_once 'models/Model.php'

	class Request extends Model {

		public function __construct($table = TABLE_REQUESTS) {
			parent::__construct($table);
		}

		public send_request($eid_from, $eid_to) {
			return $this->insert(array("eid_from" => $eid_from, "eid_to" => $eid_to));
		}

		public request_exists($eid, $eid_to) {
			$requests = $this->select('id', "WHERE (eid_from='$eid' AND eid_to='$eid_to') OR (eid_from='$eid_to' AND eid_to='$eid')");
			if(!$requests){
				return ($requests > 0);
			} else {
				return Model::SQL_FAILED;
			}
		}

	}

?>