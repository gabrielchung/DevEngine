<?php

    //Check authorization to ensure security
	include dirname(__FILE__) . '/dev_engine_ui_auth.php';

	include_once dirname(__FILE__) . '/dev_engine_ui.php';

    if (isset($_GET['object_type_name']) && isset($_GET['id']) && isset($_GET['key'])) {

        $obj = \dev_engine\ObjectQuery::retrieve(array('object_type_name'=>$_GET['object_type_name'], 'id'=>$_GET['id']));

        if (null === $obj) {
            echo '-1';
            exit;
        }

        //var_dump($obj->retrieve_custom_values());

        $customValue = $obj->retrieve_custom_value($_GET['key']);

        if (null === $customValue) {
            echo '-2';
            exit;
        }

        $imageData = \dev_engine\ui\Image::decode_image_str($customValue->description, $mimeType);

        $mimeType = \dev_engine\ui\Image::get_image_type($customValue->description);

        // var_dump($customValue->description);

        // echo 'mimeType: ';
        // var_dump($mimeType);

        //var_dump($imageData);

        header('Content-type: image/'.$mimeType);
        echo $imageData;

    } else {
        echo '-3';
    }

?>