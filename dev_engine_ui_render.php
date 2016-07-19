<?php

	//Check authorization to ensure security
	include dirname(__FILE__) . '/dev_engine_ui_auth.php';

	include_once dirname(__FILE__) . '/dev_engine_ui.php';

	\dev_engine\ui\UI::load_jquery();
	\dev_engine\ui\UI::load_jquery_ui();

	//handle request
	if (isset($_GET['action']) && isset($_GET['itemType'])) {

		switch ($_GET['action']) {
			case 'list':

				if ( ! isset($_GET['itemRowCount']) ) {
					echo 'itemRowCount is not set';
					exit;
				}
			
				if ( ! isset($_GET['offset']) ) {
					echo 'offset is not set';
					exit;
				}
			
				$objectID = null;
				//by default we generate a list with main div
				$withMainDiv = true;
				$parentObjectType = '';
				$parentObjectID = null;

				if (isset($_GET['id'])) {
					$objectID = $_GET['id'];
				}

				if (isset($_GET['withMainDiv'])) {
					$withMainDiv = $_GET['withMainDiv'];
				}

				if (isset($_GET['parentObjectType'])) {
					$parentObjectType = $_GET['parentObjectType'];
				}

				// if (isset($_GET['parentObjectID'])) {
				// 	$parentObjectID = $_GET['parentObjectID'];
				// }

				\dev_engine\ui\UI::get_list($_GET['itemType'], $_GET['itemRowCount'], $_GET['offset'], $objectID, $withMainDiv, $parentObjectType);
				// \dev_engine\ui\UI::get_list($_GET['itemType'], $objectID, $withMainDiv, $parentObjectType, $parentObjectID);
				break;

			case 'create':
				$js_completionCallBackFuncName = '';

				if (isset($_GET['js_completionCallBackFuncName'])) {
					$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
				}

				\dev_engine\ui\UI::create_item($_GET['itemType'], $js_completionCallBackFuncName);
				break;
				
			case 'create_with_parent':
				$js_completionCallBackFuncName = '';

				if (isset($_GET['js_completionCallBackFuncName'])) {
					$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
				}
				
				if ( 
							(! isset($_GET['parentItemID']))
						||	(! isset($_GET['parentItemType']))
					) {
				
					throw new Exception('Required parameters are not set');
						
				}

				\dev_engine\ui\UI::create_item_with_parent($_GET['itemType'], $js_completionCallBackFuncName, $_GET['parentItemID'], $_GET['parentItemType']);
				break;
			
			case 'edit_template':

				$js_completionCallBackFuncName = '';

				if (isset($_GET['js_completionCallBackFuncName'])) {
					$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
				}

				\dev_engine\ui\UI::edit_item_template($_GET['itemType'], $js_completionCallBackFuncName);

				break;

			case 'edit_with_parent_template':
			
				$js_completionCallBackFuncName = '';

				if (isset($_GET['js_completionCallBackFuncName'])) {
					$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
				}

				if ( 	
						(! isset($_GET['parentItemID']))
					||	(! isset($_GET['parentItemType']))
				) {
			
					throw new Exception('Required parameters are not set');
						
				}

				\dev_engine\ui\UI::edit_item_template_with_parent($_GET['itemType'], $js_completionCallBackFuncName, $_GET['parentItemID'], $_GET['parentItemType']);
			
				break;

			case 'edit':

				if (isset($_GET['id'])) {

					$js_completionCallBackFuncName = '';

					if (isset($_GET['js_completionCallBackFuncName'])) {
						$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
					}

					\dev_engine\ui\UI::edit_item($_GET['itemType'], $_GET['id'], $js_completionCallBackFuncName);
				}

				break;
				
			case 'edit_with_parent':
			
				if (isset($_GET['id'])) {

					$js_completionCallBackFuncName = '';

					if (isset($_GET['js_completionCallBackFuncName'])) {
						$js_completionCallBackFuncName = $_GET['js_completionCallBackFuncName'];
					}

					if ( 
							(! isset($_GET['parentItemID']))
						||	(! isset($_GET['parentItemType']))
					) {
				
						throw new Exception('Required parameters are not set');
							
					}

					\dev_engine\ui\UI::edit_item_with_parent($_GET['itemType'], $_GET['id'], $js_completionCallBackFuncName, $_GET['parentItemID'], $_GET['parentItemType']);
				}
			
				break;

			default:
				echo 'Cannot find corresponding action';
				break;
		}

	}

?>