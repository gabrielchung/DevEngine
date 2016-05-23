<?php

	include_once dirname(__FILE__) . '/dev_engine_ui.php';

	$userID = \dev_engine\ui\Auth::get_session_auth_userID();

	if (NULL === $userID) {

		echo '<h1>Please log in for access</h1>';
		exit;

	}

?>