<?php

	//Check authorization to ensure security
	include dirname(__FILE__) . '/dev_engine_ui_auth.php';

	include_once dirname(__FILE__) . '/dev_engine.php';

	if (isset($_POST['action'])) {

		switch ($_POST['action']) {

			case 'createItem':
			case 'createItemWithParent':
				
				if (
				   isset($_POST['itemType'])
				&& isset($_POST['itemName'])
				&& isset($_POST['itemDescription'])
				) {


					// Check if the object type has been created
					if (null === \dev_engine\DBObject::get_object_type($_POST['itemType'])) {

						echo -3;
						exit;
					}

					try {

						$obj = new \dev_engine\Object('', $_POST['itemName'], $_POST['itemDescription'], $_POST['itemType']);
						$obj->create();
						
						if ('createItemWithParent' === $_POST['action']) {
					
							if (
								   isset($_POST['parentItemID'])
								&& isset($_POST['parentItemType'])
								) {
							
								if (null === ($parentObj = \dev_engine\ObjectQuery::retrieve(array('table_name'=>$_POST['parentItemType'], 'id'=>$_POST['parentItemID'])))) {
									
									//Cannot retrieve parent
									echo -5;
									exit;
									
								} else {

									//Link the parent to the newly created child
									$parentObj->associate_relationship($_POST['parentItemType'].'_'.$_POST['itemType'], $obj->id);
									echo 2;
									exit;
									
								}
							
							} else {
								
								//Not all required parameters are supplied
								echo -4;
								exit;
								
							}
								
						}
						
						echo 1;

					} catch (Exception $ex) {
						
						//the object type may not be created and throw an exception
						echo -1;
						var_dump($ex);

					}

				}

				break;
			
			case 'updateItem':
			case 'updateItemWithParent':
				
				if (
				   isset($_POST['itemType'])
				&& isset($_POST['id'])
				&& isset($_POST['itemName'])
				&& isset($_POST['itemDescription'])
				) {

					// Check if the object type has been created
					if (null === \dev_engine\DBObject::get_object_type($_POST['itemType'])) {

						echo -3;
						exit;
					}

					try {

						$obj = \dev_engine\ObjectQuery::retrieve(array('table_name'=>$_POST['itemType'], 'id'=>$_POST['id']));

						$obj->title = $_POST['itemName'];
						$obj->description = $_POST['itemDescription'];

						$obj->update();
						
						if ('updateItemWithParent' === $_POST['action']) {
					
							if (
								   isset($_POST['parentItemID'])
								&& isset($_POST['parentItemType'])
								) {
							
								if (null === ($parentObj = \dev_engine\ObjectQuery::retrieve(array('table_name'=>$_POST['parentItemType'], 'id'=>$_POST['parentItemID'])))) {
									
									//Cannot retrieve parent
									echo -5;
									exit;
									
								} else {

									//Dislink the parent to old child's id
									$parentObj->disassociate_relationship($_POST['parentItemType'].'_'.$_POST['itemType'], $_POST['id']);

									//Link the parent to the newly created child
									$parentObj->associate_relationship($_POST['parentItemType'].'_'.$_POST['itemType'], $obj->id);
									
								}
							
							} else {
								
								//Not all required parameters are supplied
								echo -4;
								exit;
								
							}
								
						}
						
						echo 1;

					} catch (Exception $ex) {

						//the object type may not be created and throw an exception
						echo -1;

					}

				}

				break;

			case 'deleteItem':
			case 'deleteItemWithParent':
				
				if (
				   isset($_POST['itemType'])
				&& isset($_POST['id'])
				) {

					// Check if the object type has been created
					if (null === \dev_engine\DBObject::get_object_type($_POST['itemType'])) {

						echo -3;
						exit;
					}

					try {

						$obj = \dev_engine\ObjectQuery::retrieve(array('table_name'=>$_POST['itemType'], 'id'=>$_POST['id']));

						$obj->delete();
						
						if ('deleteItemWithParent' === $_POST['action']) {
					
							if (
								   isset($_POST['parentItemID'])
								&& isset($_POST['parentItemType'])
								) {
							
								if (null === ($parentObj = \dev_engine\ObjectQuery::retrieve(array('table_name'=>$_POST['parentItemType'], 'id'=>$_POST['parentItemID'])))) {
									
									//Cannot retrieve parent
									echo -5;
									exit;
									
								} else {

									echo 'here';

									//Dislink the parent to old child's id
									$parentObj->disassociate_relationship($_POST['parentItemType'].'_'.$_POST['itemType'], $_POST['id']);
									
									echo 'here2';
									
								}
							
							} else {
								
								//Not all required parameters are supplied
								echo -4;
								exit;
								
							}
								
						}
						
						echo 1;

					} catch (Exception $ex) {

						//the object type may not be created and throw an exception
						echo -1;

					}

				}

				break;

			default:
				echo -2;
				break;

		}

	}

?>