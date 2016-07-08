<?php

namespace dev_engine\ui;

	include_once dirname(__FILE__) . '/dev_engine.php';

	class Auth {

		const devEngineUISessionUserID = 'devEngineUIAuthUserID';

		public static function get_session_auth_userID() {

			session_start();

			if ( ! isset($_SESSION[\dev_engine\ui\Auth::devEngineUISessionUserID]) ) {

				return NULL;

			} else {

				if ( $_SESSION[\dev_engine\ui\Auth::devEngineUISessionUserID] > -1 ) {

					return $_SESSION[\dev_engine\ui\Auth::devEngineUISessionUserID];

				} else {

					return NULL;

				}

			}

		}

		public static function set_session_auth_userID($userID) {
			
			session_start();

			$_SESSION[\dev_engine\ui\Auth::devEngineUISessionUserID] = $userID;

		}

		public static function unset_session_auth_userID() {
			
			session_start();

			unset($_SESSION[\dev_engine\ui\Auth::devEngineUISessionUserID]);

		}

	}

	class VariableName {
		
		const spaceStr = '_space_';
		
		public static function convert_to_special_space($value) {
			return str_replace(' ', VariableName::spaceStr, $value);
		}
		
		public static function convert_from_special_space($value) {
			return str_replace(VariableName::spaceStr, ' ', $value);
		}
		
		public static function convert_spaces_to_underscores($value) {
			return str_replace(' ', '_', $value);
		}
		
	}
	
	class Data {
		
		public static function update_obj_values($objectType, $objectID, $displayFilter, $aryKeyValuePairs) {
			
			$obj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$objectType, 'id'=>$objectID));
			
			if (null === $obj) {
				
				throw new \Exception('Object cannot be found');
				
			}
			
			foreach ($displayFilter as $tmpDisplayFilterObj) {
			
				$displayFilterObj = (array) $tmpDisplayFilterObj; //convert the standard object into array
				
				if ('displayFilter' === $displayFilterObj['key']) {

					//system variable
					
					//do nothing
			
				} elseif ('slug' === $displayFilterObj['key']) {
			
					$obj->slug = $aryKeyValuePairs[$displayFilterObj['key']];
			
				} elseif ('title' === $displayFilterObj['key']) {
					
					$obj->title = $aryKeyValuePairs[$displayFilterObj['key']];
					$obj->update();
					
				} elseif ('description' === $displayFilterObj['key']) {
					
					$obj->description = $aryKeyValuePairs[$displayFilterObj['key']];
					$obj->update();
										
				} else {
					
					$obj->update_custom_value(
												 $displayFilterObj['key']
												,$aryKeyValuePairs[$displayFilterObj['key']]
											 );
					
				}
				
			}
			
		}
		
	}

	class UserControl {
		
		public static function gen_controls($displayFilter, $aryObjValuesKeyValuePairs) {
			
			?>
			<form method="post">			
			<?php
			
			foreach ($displayFilter as $displayFilterObj) {
				
				if ( isset($aryObjValuesKeyValuePairs[$displayFilterObj['key']]) ) {
					$controlValue = $aryObjValuesKeyValuePairs[$displayFilterObj['key']];
				} else {
					$controlValue = null;
				}
				
				UserControl::gen_control($displayFilterObj['type']
										,$displayFilterObj['key']
										,$controlValue);
				
			}
			
			?>
			<input type="submit" value="Submit" />
			</form>
			<?php
			
		}
		
		private static function gen_control($controlType, $key, $value) {
			
			if (null === $value) {
				$value = '';
			}
			
			switch ($controlType) {
				
				case 'hidden':
					?>
					<input type="hidden" name="<?php echo VariableName::convert_to_special_space($key); ?>" value="<?php echo $value; ?>" />
					<?php
					break;
				
				case 'textbox':
					?>
					<div><?php echo ucfirst($key); ?>: <input type="text" name="<?php echo VariableName::convert_to_special_space($key); ?>" value="<?php echo $value; ?>" /></div>
					<?php
					break;
					
				case 'textarea':
					?>
					<div><?php echo ucfirst($key); ?>: <textarea name="<?php echo VariableName::convert_to_special_space($key); ?>"><?php echo $value; ?></textarea></div>
					<?php
					break;
				
				case 'checkbox':
					?>
					<input type="hidden" id="bizsys_checkbox_hidden_<?php echo VariableName::convert_to_special_space($key); ?>" name="<?php echo VariableName::convert_to_special_space($key); ?>" value="<?php if (!empty($value)) { echo $value; } else { echo 'false'; } ?>" />
					<div><label><?php echo ucfirst($key); ?>: <input id="bizsys_checkbox_<?php echo VariableName::convert_to_special_space($key); ?>" type="checkbox" <?php if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) echo 'checked'; ?> /></label></div>
					<script>
						$('#bizsys_checkbox_<?php echo VariableName::convert_to_special_space($key); ?>').on('click', function(){
							
							var resultValue = ($('#bizsys_checkbox_<?php echo VariableName::convert_to_special_space($key); ?>').is(':checked'));

							$('#bizsys_checkbox_hidden_<?php echo VariableName::convert_to_special_space($key); ?>').val(resultValue);

						});
					</script>
					<?php
					break;

				case 'imageUpload':
					$jsKeyID = VariableName::convert_spaces_to_underscores($key);
					?>
					<script>
						var dev_engine_ui_uploadImageKeyID = '<?php echo $jsKeyID; ?>';
						function dev_engine_ui_uploadImageHandle(elemID, base64ImgElemID, previewElemID) {
							
							var uploadImageElem = document.getElementById(elemID+'_'+dev_engine_ui_uploadImageKeyID);
							
							if (uploadImageElem.files && uploadImageElem.files[0]) {
								var FR = new FileReader();
								FR.onload = function(e) {
									document.getElementById(base64ImgElemID+'_'+dev_engine_ui_uploadImageKeyID).value = e.target.result;
									//document.getElementById(previewElemID+'_'+keyID).src = e.target.result;
									dev_engine_ui_uploadImagePreview(base64ImgElemID, previewElemID);
								}
								
								FR.readAsDataURL(uploadImageElem.files[0]);
							}
						}
						
						function dev_engine_ui_uploadImagePreview(elemID, previewElemID) {
							document.getElementById(previewElemID+'_'+dev_engine_ui_uploadImageKeyID).src = document.getElementById(elemID+'_'+dev_engine_ui_uploadImageKeyID).value;
						}
						
					</script>
					<div><?php echo ucfirst($key); ?>: 
						<input onchange="dev_engine_ui_uploadImageHandle('dev_engine_ui_uploadImage', 'dev_engine_ui_uploadImageBase64Str', 'dev_engine_ui_imagePreview')" id="dev_engine_ui_uploadImage_<?php echo $jsKeyID; ?>" type="file" />
						<input id="dev_engine_ui_uploadImageBase64Str_<?php echo $jsKeyID; ?>" type="text" style="display:none;" name="<?php echo VariableName::convert_to_special_space($key); ?>" value="<?php echo $value; ?>">
						<img id="dev_engine_ui_imagePreview_<?php echo $jsKeyID; ?>" /></div>
					<script>dev_engine_ui_uploadImagePreview('dev_engine_ui_uploadImageBase64Str', 'dev_engine_ui_imagePreview');</script>
					<?php
					break;
				
				default:
					throw new \Exception('Control type ('.$controlType.') cannot be found');
					break;
			}
			
			
		}
		
	}

	class UI {

		public static function load_dev_engine_ui_head_files() {
			
			UI::load_dev_engine_ui_js();
			
			UI::load_dev_engine_ui_css();
			
		}

		private static function load_dev_engine_ui_js() {
			
			?>
			
			<script src="<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/js/dev_engine_ui.js"></script>
			
			<?php
			
		}

		private static function load_dev_engine_ui_css() {
			
			?>
			
			<link rel="stylesheet" type="text/css" href="<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/css/dev_engine_ui.css">
			
			<?php
			
		}

		//Load JQuery if necessary
		public static function load_jquery() {
			
			//assume jquery is NOT loaded for now

			?>

			<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.2/jquery.min.js"></script>

			<?php

		}
		
		public static function load_jquery_ui() {
			
			?>
			
			<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css">
			<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
			
			<?php
			
		}

		public static function get_list($objectType, $objectRowCount, $offset=0, $objectID=null, $withMainDiv=false, $parentObjectType='') {

			if ( ! isset($objectRowCount) ) {
				echo 'object row count is not set';
				exit;
			}

			if ($withMainDiv) {
				?>
					<script>
						//
						//Main Div script section
						//
						var dialogRandNum = -1;
						var jqyuiDialogOptions = { autoOpen: false, modal: true };
						var offset = <?php echo $offset; ?>;
						var itemRowCount = <?php echo $objectRowCount; ?>;
						
						function genDialogRandNum() {
							var randNum = Math.trunc(Math.random() * 100000000000)
							dialogRandNum = randNum;
						}
						
						function getDialogID() {
							var result = 'getList_itemDialog'+dialogRandNum;
							return result;
						}
						
						function ajaxCompletionCallBack() {
							
							closeDialog();
							
							getList_refreshMainDiv();
							
						}

						function closeDialog() {
							
							// if ($('#'+getDialogID()).hasClass('ui-dialog-content')) {
							// 	$('#'+getDialogID()).dialog(jqyuiDialogOptions);
							// 	$('#'+getDialogID()).dialog('close');
							// }
							
							//dialog('close') is not working... So we trigger a click on the close button
							$($('.ui-dialog-titlebar-close')[0]).trigger('click');
							
							//$('#getList_itemDialog').dialog(jqyuiDialogOptions);
							//if ($('#getList_itemDialog').hasClass('ui-dialog-content')) {
							//if ($('#getList_itemDialog').dialog('isOpen')) {
								// $('#getList_itemDialog').dialog(jqyuiDialogOptions);
								// $('#getList_itemDialog').dialog('close');
							//}
							
							//clear all dialog elements
							$('.ui-dialog').remove();
							$('.ui-widget-overlay').remove();
							
							//clear all functions
							$('#'+getDialogID()).remove();
							
							
						}

						function openDialog(content) {
							
							$('#getList_itemDialog').html('<div id="'+getDialogID()+'">'+content+'</div>');
							
							$('#'+getDialogID()).dialog(jqyuiDialogOptions);
							
							$('#'+getDialogID()).dialog('open');
							
						}
						
						function getList_refreshMainDiv() {
							getList_refreshMainDivWithOffset(offset, itemRowCount);
						}
						
						function getList_refreshMainDivWithOffset(refreshOffset, refreshItemRowCount) {

							$('#getList_mainDiv').append('<br />Loading ...');

							var args = {itemType: '<?php echo $objectType; ?>', action: 'list', withMainDiv: false, offset: refreshOffset, itemRowCount: refreshItemRowCount};
							
							<?php
								if (null !== $objectID) {
									?>
									args['id'] = <?php echo $objectID; ?>;		
									<?php
								}
								
								if (null !== $parentObjectType) {
									?>
									args['parentObjectType'] = '<?php echo $parentObjectType; ?>';		
									<?php
								}
							?>

							$.get('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_render.php', args,
								function(resultData) {

									if (-1 != resultData) {
										$('#getList_mainDiv').html(resultData);
									}

								}
							);

						}
					</script>
				<?php
				echo '<div id="getList_mainDiv">';
			}

			if ( (null === $objectID) || empty($parentObjectType) ) {
			
				//Just load all items for that object
				
				$args = array('object_type_name'=>$objectType);

				$totalObjectCount = \dev_engine\ObjectQuery::objCount($args);	

				$args['offset'] = $offset;
				$args['retrieve_row_count'] = $objectRowCount;
				
				$aryObj = \dev_engine\ObjectQuery::retrieve($args);

			} else {
				
				//Initialize relatinoship
				\dev_engine\DBObject::register_new_relationship($parentObjectType, $objectType);
				
				//Load all items for the relationship
				
				if (null === ($parentObj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$parentObjectType, 'id'=>$objectID)))) {

					throw new \Exception('Parent object cannot be retrieved');
					
				} else {


					
					// get total item count
					
					$totalObjectCount = $parentObj->get_all_relationship_children_items($parentObjectType . '_' . $objectType, 0, 0, false, true);

					
					
					// get the list items

					$aryObj = array();
					
					$aryRelationshipObj = $parentObj->get_all_relationship_children_items($parentObjectType . '_' . $objectType, $offset, $objectRowCount);
					
					$aryRelationshipObj = $aryRelationshipObj === null ? array() : $aryRelationshipObj;
					
					foreach ($aryRelationshipObj as $relationshipObj) {
						
						if (null !== ($obj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$objectType, 'id'=>$relationshipObj->secondary_id)))) {
							
							array_push($aryObj, $obj);
							
						}
						
					}
					
				}		
				
			}

			if ( (null === $objectID) || empty($parentObjectType) ) {
				$withParent = false;
			} else {
				$withParent = true;		
			}

				?>
				<script>
					
					//
					//Inside Main Div script section
					//
					
					var currItemType = '<?php echo $objectType; ?>';
					var selectedItems = [];
					var currDeletedItemsCount = -1;
					var totalItemCount = <?php echo $totalObjectCount; ?>;
					var withParent = <?php if ( $withParent ) { echo 'true'; } else { echo 'false'; } ?>;
					 
				<?php
				
				if ( ! ( (null === $objectID) || empty($parentObjectType) ) ) {
				?>
					var itemID = <?php echo $objectID; ?>;
					var parentObjectType = '<?php echo $parentObjectType; ?>';
				<?php
				}
						
				//Load item list data to javascript
				$javascriptObjDataOutput = '';
				
				foreach ($aryObj as $obj) {
					
					$javascriptObjDataOutput .= ', ' . json_encode(array('id'=>$obj->id, 'title'=>$obj->title), JSON_FORCE_OBJECT);
					
					//echo '<li class="item unselected-item" unselectable="on" data-id="' . $obj->id. '">' . $obj->title . '</li>';
				}				
				
				$javascriptObjDataOutput = substr($javascriptObjDataOutput, 2); //remove the first comma and space
				$javascriptObjDataOutput = '[' . $javascriptObjDataOutput . ']';
				
				echo 'var itemData = ' . $javascriptObjDataOutput . ';';
				
				?>
					
					<?php
					// parentItemType
					if ('' !== $parentObjectType) {
					?>
					var parentItemType = '<?php echo $parentObjectType; ?>';
					<?php
					}

					//currItemID
					if (null !== $objectID) {
					?>
					var objectID = <?php echo $objectID; ?>;
					<?php
					}
					?>

					function getList_createItem() {
						
						var action = 'create';
						
						//prepare getData
						var getData = {itemType: currItemType, action: action, js_completionCallBackFuncName: 'ajaxCompletionCallBack'};
						
					<?php
					if ($withParent) {
					?>
						// if (withParent) {
							getData['action'] = 'create_with_parent';
							getData['parentItemID'] = itemID;
							getData['parentItemType'] = parentItemType;
						// }
					<?php
					}
					?>							
						$.get('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_render.php', getData,
								function(resultData) {
									if (-1 != resultData) {
										//$('#getList_item').html(resultData);
										//$('#'+getDialogID()).html(resultData);
										
										openDialog(resultData);
									}
								}
							);
					}

					function getList_openItems() {
						
						if (0 === selectedItems.length) {
							
							return;
							
						} else if (1 === selectedItems.length) {
							
							//Only one selected item
							goToCurrItemTypeObjWithID(selectedItems[0], false);
							
						} else {
							
							//Multiple selected items
							return;
							
							// for (var i=0; i<selectedItems.length; i++) {
							// 	goToCurrItemTypeObjWithID(selectedItems[i], true);
							// }
							
						}
						
					}

					function getList_editItems() {
						
						if (0 === selectedItems.length) {
							
							return;
							
						} else if (1 === selectedItems.length) {
							
							//Only one selected item
							var getData = {itemType: currItemType, action: 'edit', id: selectedItems[0], js_completionCallBackFuncName: 'ajaxCompletionCallBack'};
							
					<?php
						if ($withParent) {
					?>
							// if (withParent) {
								getData['action'] = 'edit_with_parent';
								getData['parentItemID'] = itemID;
								getData['parentItemType'] = parentItemType;
							// }
					<?php
						}
					?>		
							$.get('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_render.php', getData,
								function(resultData) {
									if (-1 != resultData) {
										//$('#getList_item').html(resultData);
										//$('#'+getDialogID()).html(resultData);
										openDialog(resultData);
									}
								}
							);
							
						} else {
							
							//Multiple selected items
							return;
							
						}

					}
					
					function getList_deleteItems() {

						currDeletedItemsCount = 0;

						for (var i=0; i<selectedItems.length; i++) {
							
							//console.log('itemID: '+selectedItems[i]);
							
							getList_deleteItem(selectedItems[i], selectedItems.length);
							
						}
						
					}
					
					function getList_deleteItem(deleteItemID, targetTotalDeletedItemsCount) {
						
						var postData =
										{action: 'deleteItem'
										,itemType: currItemType
										,id: deleteItemID};
						
					<?php
						if ($withParent) {
					?>
						// if (withParent) {
							postData['action'] = 'deleteItemWithParent';
							postData['parentItemID'] = itemID;
							postData['parentItemType'] = parentItemType;
						// }
					<?php
						}
					?>
						
						$.post('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_ajax.php', postData, function(resultData) {
							
							currDeletedItemsCount++;
							
							if (currDeletedItemsCount === targetTotalDeletedItemsCount) {

								//the last delete item ajax call							
								getList_refreshMainDiv();

							}
							
							//console.log(postData);
							//console.log(resultData)
							
						});
					}

					function goToCurrItemTypeObjWithID(id, openInNewWindow) {
						
						var itemLinkUrl = './' + currItemType + '.php?id=' + id;

						if ( window['currItemID'] != undefined ) {
							//itemLinkUrl += '&' + parentItemType.toLowerCase() + '_id=' + parentItemID;
							itemLinkUrl += '&' + currItemType.toLowerCase() + '_id=' + currItemID;
						}

						if (openInNewWindow) {
							window.open(itemLinkUrl, '_blank');
						} else {
							window.location.href = itemLinkUrl;
						}
							
					}

					//pagination functions
					function updateItemRowCount() {
						
						var pageRowCount = $('#pageRowCount').val();
						
						if (isInt(pageRowCount)) {
						
							itemRowCount = parseInt($('#pageRowCount').val());
							return true;
						
						} else {
							
							return false;
							
						}
						
					}
					
					function getPageNumber() {
						return Math.trunc(offset / itemRowCount) + 1;
					}
					
					function getTotalPageNumber() {
						return Math.trunc( ( (totalItemCount - 1) / itemRowCount ) + 1);
					}
					
					function prevPage() {
						
						updateItemRowCount();
						
						var currPageNumber = getPageNumber();
						
						if (1 === currPageNumber) {
							
							//do nothing if we are at the first page
							return;
							
						} else {
							
							var prevPageNumber = currPageNumber - 1;
							var prevPageOffset = (prevPageNumber - 1) * itemRowCount;
							
							//update the offset of the page
							offset = prevPageOffset;
							
							getList_refreshMainDiv();
							
						}
					}
					
					function nextPage() {
						
						updateItemRowCount();
						
						var currPageNumber = getPageNumber();
						
						if ( getTotalPageNumber() === currPageNumber ) {
							
							// larger than the last page number
							return;
							
						} else {
							
							var nextPageNumber = currPageNumber + 1;
							var nextPageOffset = (nextPageNumber - 1) * itemRowCount;
							
							//update the offset of the page
							offset = nextPageOffset;
							
							getList_refreshMainDiv();
							
						}
					}
					
					function changePage(pageNumber) {
						
						if ( pageNumber < 1 ) {
							
							// smaller than the first page number
							return;
						
						} else if ( getTotalPageNumber() < pageNumber ) {
							
							// larger than the last page number
							return;
							
						} else {
						
							updateItemRowCount();
							
							var currPageNumber = getPageNumber();
							
							var newPageOffset = (pageNumber - 1) * itemRowCount;
							
							//update the offset of the page
							offset = newPageOffset;
							
							getList_refreshMainDiv();
						
						}
						
					}
					
					function changePageRowCount(pageRowCount) {
						
						if ( pageRowCount < 1) {
							
							//row count cannot be smaller than 1
							return false;
							
						} else {
							
							var result = updateItemRowCount();
							
							if (result) {
								
								updateTotalPageNumber();
								return true;
								
							} else {
								
								return false;
								
							}
							
						}
						
					}
					
					function updateTotalPageNumber() {
						
						$('#totalPageNumber').text( getTotalPageNumber() );
						
					}

					//initialization functions

					function setItemsClickFunction() {
						
						$('.item').click(function(){
							
							if ($(this).hasClass('selected-item')) {

								//Unselect
								
								//selected items
								var currItemIndex = selectedItems.indexOf($(this).data('id'));
								selectedItems.splice(currItemIndex, 1);
								
								//style
								$(this).addClass('unselected-item');
								$(this).removeClass('selected-item');
																
							} else {
								
								//Select
								
								//selected items
								selectedItems.push($(this).data('id'));
								
								//style
								$(this).addClass('selected-item');
								$(this).removeClass('unselected-item');
								
							}

						});
						
					}
					
					function populateItemDataToListControl() {
						
						var itemListHTMLContent = '';
						
						for (var i=0; i<itemData.length; i++) {
							itemListHTMLContent += '<li class="item unselected-item" unselectable="on" data-id="' + itemData[i].id + '">' + itemData[i].title + '</li>';
						}
						
						$('#list').html(itemListHTMLContent);
						
						//echo '<li class="item unselected-item" unselectable="on" data-id="' . $obj->id. '">' . $obj->title . '</li>';
					}
					
					function updatePageControlValues() {
						
						$('#pageNumber').val( getPageNumber() );
						
						$('#pageRowCount').val( itemRowCount );
					
					    updateTotalPageNumber();
						
					}
					
					function bindPageNumberTextbox() {
						
						$('#pageNumber').keyup(function(e) {
							
							var pageNumber = $('#pageNumber').val();
							
							if (isInt(pageNumber)) {
								
								$('#pageNumber').removeClass('highlighted-textbox');
								
							} else {
								
								$('#pageNumber').addClass('highlighted-textbox');
								
							}
							
							//capture enter key
							if (13 == e.which) {
								
								if (isInt(pageNumber)) {

									changePage(parseInt(pageNumber));
									
								}
								
								//ignore other behaviors
								e.preventDefault();
							}
							
						});
						
					}
					
					function bindItemRowCountTextbox() {
						
						$('#pageRowCount').keyup(function(e){
							
							var pageRowCount = $('#pageRowCount').val();
							
							if (isInt(pageRowCount)) {
								
								if ( changePageRowCount(pageRowCount) ) {
									
									$('#pageRowCount').removeClass('highlighted-textbox');
									return;
									
								}
								
							}
							
							//there is error for this textbox
							$('#pageRowCount').addClass('highlighted-textbox');
							
						});
						
					}

					$(document).ready(function(){

						populateItemDataToListControl();

						bindPageNumberTextbox();
						
						bindItemRowCountTextbox();

						// Set click link on list items
						setItemsClickFunction();
						
						updatePageControlValues();
						
					});
				</script>
				<div id="main">
					<div id="headPanel" class="controlPanel">
						<button onclick="getList_createItem()">+</button>
					</div>
            		<ul id="list">
					<?php

					// if (null === $aryObj) {
					// 	$aryObj = array();
					// }

					// foreach ($aryObj as $obj) {
					// 	echo '<li class="item unselected-item" unselectable="on" data-id="' . $obj->id. '">' . $obj->title . '</li>';
					// }

					?>
					</ul>
					<div id="footPanel" class="controlPanel">
						<button onclick="getList_openItems();">Open</button>
						<button onclick="getList_editItems();">Edit</button>
						<button onclick="getList_deleteItems();">Delete</button>
						<span id="pageControlsDiv">
							<button onclick="prevPage();"> < </button>
							<input id="pageNumber" type="text" /> of <span id="totalPageNumber"></span>
							<button onclick="nextPage();"> > </button>
							<input id="pageRowCount" type="text" /> Items
						</span>
					</div>
				</div>
				<?php //Item panel ?>
				<div style="display:none;">
				Item:<br />
				<div id="getList_item"></div>
				</div>
				<?php

				if ($withMainDiv) {
					echo '</div>';
				}
				?>
				<div id="getList_itemDialog"></div>
				<?php
			//}

		}

		public static function create_item($objectType, $js_completionCallBackFuncName='') {
			UI::create_item_with_parent($objectType, $js_completionCallBackFuncName);
		}

		public static function create_item_with_parent($objectType, $js_completionCallBackFuncName='', $parentObjectID=null, $parentObjectType='') {
			
			if ( (null === $objectID) || empty($parentObjectType) ) {
				$withParent = false;
			} else {
				$withParent = true;		
			}
			?>
			<script>
				function createItem() {
					var itemType = '<?php echo $objectType; ?>';
					var itemName = $('#itemName').val();
					var itemDescription = $('#itemDescription').val();
					var completionCallBackFuncName = '<?php echo $js_completionCallBackFuncName; ?>';

					var dataObj =
									{action: 'createItem'
									,itemType: itemType
									,itemName: itemName
									,itemDescription: itemDescription};

			<?php
			if ( $withParent ) {
			?>
					// if (withParent) {	
						dataObj['action'] = 'createItemWithParent';
						dataObj['parentItemID'] = <?php echo $parentObjectID; ?>;
						dataObj['parentItemType'] = '<?php echo $parentObjectType; ?>';
					// }
			
			<?php	
			}
			?>
					

					$.post('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_ajax.php', dataObj, function(resultData) {
						if (resultData > 0) {
							
							//success
							$('#createItem').hide();

							if ('' !== completionCallBackFuncName)	{
								window[completionCallBackFuncName]();
							}

						} else {
							console.log('Item creation failed. Error code = '+resultData);
						}
						
						//console.log(dataObj);
						//console.log(resultData);
					});
				}
			</script>
			<div id="createItem">
			Item Name: <input id="itemName" type="text" /><br />
			Description: <textarea id="itemDescription"></textarea><br />
			<button id="btnItemCreate" onclick="createItem();">Create</button>
			</div>
			<?php
		}
		
		public static function get_single($objectType, $objectID, $displayFilter=null) {
			
			$obj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$objectType, 'id'=>$objectID));
    
			if (null === $obj) {
				echo 'Product cannot be found';
				exit;
			}
			
			// Display Filter
			
			$finalDisplayFilter=array(
								 array('key'=>'displayFilter','type'=>'hidden')
								,array('key'=>'title','type'=>'textbox')
								,array('key'=>'description','type'=>'textarea')
							);
			
			if (null !== $displayFilter) {
			
				foreach ($displayFilter as $displayFilterItem) {
			
					array_push($finalDisplayFilter, $displayFilterItem);
				
				}
							
			}
			
			// Object Values
			
			$aryObjValuesKeyValuePairs = array('displayFilter'=>htmlentities(json_encode($finalDisplayFilter), ENT_QUOTES)
											  ,'title'=>$obj->title
											  ,'description'=>$obj->description
											  );
			
			if (null !== ($customValues = $obj->retrieve_custom_values())) {
				
				foreach ($customValues as $customValueObj) {
					
					$aryObjValuesKeyValuePairs[$customValueObj->slug] = $customValueObj->description;
					
				}

			}

			\dev_engine\ui\UserControl::gen_controls($finalDisplayFilter, $aryObjValuesKeyValuePairs);
						
		}

		public static function edit_item($objectType, $objectID, $js_completionCallBackFuncName='') {
			UI::edit_item_with_parent($objectType, $objectID, $js_completionCallBackFuncName);
		}

		public static function edit_item_with_parent($objectType, $objectID, $js_completionCallBackFuncName='', $parentObjectID=null, $parentObjectType='') {

			$obj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$objectType, 'id'=>$objectID));

			if (null === $obj) {
				
				echo 'Item cannot be found.';

			} else {
				
				if ( (null === $objectID) || empty($parentObjectType) ) {
					$withParent = false;
				} else {
					$withParent = true;		
				}

				?>
				<script>
					function updateItem() {
						var itemType = '<?php echo $objectType; ?>';
						var itemID = <?php echo $objectID; ?>;
						var itemName = $('#itemName').val();
						var itemDescription = $('#itemDescription').val();
						var completionCallBackFuncName = '<?php echo $js_completionCallBackFuncName; ?>';

						var dataObj =
										{action: 'updateItem'
										,itemType: itemType
										,id: itemID
										,itemName: itemName
										,itemDescription: itemDescription};

						<?php
						if ( $withParent ) {
						?>
						//if (withParent) {	
								dataObj['action'] = 'updateItemWithParent';
								dataObj['parentItemID'] = <?php echo $parentObjectID; ?>;
								dataObj['parentItemType'] = '<?php echo $parentObjectType; ?>';
						//}
						<?php	
						}
						?>

						$.post('<?php echo \dev_engine\DevEngine::get_dev_engine_path(); ?>/dev_engine_ui_ajax.php', dataObj, function(resultData) {
							
							if (1 == resultData) {
							
								//success
								$('#editItem').hide();

								if ('' !== completionCallBackFuncName)	{
									window[completionCallBackFuncName]();
								}

							} else {
								console.log('Item update failed.');
							}
						});
					}
				</script>
				<div id="editItem">
				Item Name: <input id="itemName" type="text" value="<?php echo $obj->title; ?>" /><br />
				Description: <textarea id="itemDescription"><?php echo $obj->description; ?></textarea><br />
				<button id="btnItemUpdate" onclick="updateItem();">Update</button>
				</div>
				<?php
			
			}
		}

	}

?>