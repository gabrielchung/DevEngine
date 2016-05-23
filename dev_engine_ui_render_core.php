<?php

namespace dev_engine\ui;

include_once dirname(__FILE__) . '/dev_engine_ui.php';

class Render {

	public static function get_list($objectType, $objectRowCount, $offset=0, $objectID=null, $withMainDiv=true, $parentObjectType='') {
	//public static function get_list($objectType, $objectID=null, $withMainDiv=true, $parentObjectType='', $parentObjectID=null) {

		\dev_engine\ui\UI::get_list($objectType, $objectRowCount, $offset, $objectID, $withMainDiv, $parentObjectType);
		//\dev_engine\ui\UI::get_list($objectType, $objectID, $withMainDiv, $parentObjectType, $parentObjectID);

	}

	//TO-DO: add create_with_parent

	public static function create($objectType, $js_completionCallBackFuncName='') {

		\dev_engine\ui\UI::create_item($objectType, $js_completionCallBackFuncName);

	}

	//TO-DO: add edit_with_parent

	public static function edit($objectType, $id, $js_completionCallBackFuncName='') {

		\dev_engine\ui\UI::edit_item($objectType, $id, $js_completionCallBackFuncName);

	}

	//TO-DO: add delete and delete_with_parent

}

?>