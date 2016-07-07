<?php 

namespace dev_engine;

	include dirname(__FILE__) . '/dev_engine_config.php';

	define('BASE_OBJECT_TYPE_OBJECT_TYPE_ID', 0);
	define('BASE_OBJECT_TYPE_SLUG', 'object_type');
	define('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG', 'object_relationship_type');
	//define('BASE_OBJECT_RELATIONSHIP_SLUG', 'object_relationship');
	define('BASE_CUSTOM_VALUE_TYPE_SLUG', 'custom_value');
	define('META_VALUE_KEY_TABLE_NAME', 'table_name');

	class DevEngine {
		
		private static function is_secure() {
            return
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || $_SERVER['SERVER_PORT'] == 443;
        }
		
		public static function get_dev_engine_path() {
			
			$dev_engine_path = dirname(__FILE__); //assume this file is in the dev engine root path
            $request_uri_path = dirname($_SERVER['REQUEST_URI']);
			$absolute_request_uri_path = getcwd();

			$documentRoot = substr($absolute_request_uri_path, 0, strpos($absolute_request_uri_path, $request_uri_path));

			return (DevEngine::is_secure() ? 'https://' : 'http://')
					. $_SERVER['SERVER_NAME'] . '/'
					. substr($dev_engine_path, strlen($documentRoot));
			
			
			// return (DevEngine::is_secure() ? 'https://' : 'http://')
            //         . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']
					
			// 		. '<br />'
					
			// 		. dirname(__FILE__)
			// 		;
			
			//return $_SERVER['PHP_SELF'];
			
			//return dirname(__FILE__);
			
		} 
		
	}

	class Database {

		const c_system_table_name = 'system_table';
				
		private static function create_database($db_name) {
			
			$dbh = Database::connect(null, false);
			
			$query = $dbh->prepare( 'CREATE SCHEMA IF NOT EXISTS ' . $db_name );
			$query->execute();
			
		}
		
		public static function connect($db_name = null, $with_db = true) {
	          
            if ($db_name === null) {
            	
            	if (isset($GLOBALS['g_db_name']) && (!empty($GLOBALS['g_db_name']))) {

            		$db_name = $GLOBALS['g_db_name'];
            		
            	} else {

            		$db_name = \dev_engine\Config::DatabaseName;
            		
            	}
            	
            }
            
            if ($with_db) {
            
	            if ( !Database::check_if_schema_exists($db_name) ) {
	            	
	            	Database::create_database($db_name);
	            	
	            	Database::create_db_tables();
	            	
	            }
            
            }
            
            $dbh = new \PDO('mysql:host='.\dev_engine\Config::Address . ( $with_db ? ';dbname='.$db_name : '' ), \dev_engine\Config::Login, \dev_engine\Config::Password);

            $dbh->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

            return $dbh;
            
		}

		private static function check_if_schema_exists($db_name) {
			
			$dbh = Database::connect(null, false);
			
			$query = $dbh->prepare('SELECT COUNT(SCHEMA_NAME) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :db_name');
			$query->bindParam(':db_name', $db_name);
			
			$query->execute();
			
			$row = $query->fetch();
			
			if (0 == $row[0])
				return false;
			else
				return true;
			
		}
		
		private static function create_db_tables() {
			Database::create_object_table(Database::c_system_table_name);
		}
		
		public static function create_object_table($table_name) {
			
			$dbh = Database::connect();
			
			//It is okay to use string concat because it is an internal function and will not expose to user's input causing SQL injection. 			
			$sql_statement  = '';
			$sql_statement .= 'CREATE TABLE IF NOT EXISTS `'.$table_name.'` ( ';
			$sql_statement .= '`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT, ';
			$sql_statement .= '`slug` varchar(1000) NOT NULL, ';
			$sql_statement .= '`title` varchar(1000) NOT NULL, ';
			$sql_statement .= '`description` varchar(15000) NOT NULL, ';
			$sql_statement .= '`object_type_id` bigint(20) unsigned NOT NULL, ';
			//$sql_statement .= '`relationship_type_id` bigint(20) unsigned NOT NULL DEFAULT \'0\', ';
			$sql_statement .= '`primary_id` bigint(20) unsigned NOT NULL DEFAULT \'0\', ';
			$sql_statement .= '`secondary_id` bigint(20) unsigned NOT NULL DEFAULT \'0\', ';
			$sql_statement .= '`creation_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ';
			$sql_statement .= '`is_deleted` bit(1) NOT NULL DEFAULT b\'0\', ';
			$sql_statement .= 'PRIMARY KEY (`id`) ';
			$sql_statement .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8; ';

			$query = $dbh->prepare($sql_statement);
			$query->execute();
			
		}
	}

	class DBObject {

		//Data
		public static function create_or_load_obj_with_data($row, $table_name=null, $curr_obj=null) {
			
			if (null === $curr_obj) {
				$curr_obj = new \dev_engine\Object();
			}
			
			$curr_obj->id = $row['id'];
			$curr_obj->slug = $row['slug'];
			$curr_obj->title = $row['title'];
			$curr_obj->description = $row['description'];
			$curr_obj->object_type_id = $row['object_type_id'];
			//$curr_obj->relationship_type_id = $row['relationship_type_id'];
			$curr_obj->primary_id = $row['primary_id'];
			$curr_obj->secondary_id = $row['secondary_id'];
			$curr_obj->is_deleted = $row['is_deleted'];

            if (null !== $table_name) {
                $curr_obj->table_name = $table_name;
                return;
            }

            //excluding for table_name (which means they belong to dynamic table_name)
            // 1. custom_value_type
            // 2. object_relationship_type
            switch ($curr_obj->slug) {
                case constant('BASE_OBJECT_TYPE_SLUG'):
                    $curr_obj->table = Database::c_system_table_name;
                    break;

                case constant('BASE_CUSTOM_VALUE_TYPE_SLUG'):
                case constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'):
                    //set to null for dynamic table_name
                    $curr_obj->table_name = null;
                    break;

                default:

                    if (DBObject::get_base_object_type()->id == $curr_obj->object_type_id) {

                        //object type object

                        //table_name will be null if the object type does not have a table_name meta value
                        $obj_custom_value = DBObject::retrieve_custom_value_by_object_id(Database::c_system_table_name, $curr_obj->id, constant('META_VALUE_KEY_TABLE_NAME'));

						//custom value is changed to description
						$curr_obj->table_name = (null === $obj_custom_value ? null : $obj_custom_value->description);
						//$curr_obj->table_name = (null === $obj_custom_value ? null : $obj_custom_value->title);

                    } else {

                        //object item

                        //
                        //retrieve the table_name meta value from the object_type of the object item
                        //
                        //table_name will be null if the object type does not have a table_name meta value
                        //
                        $obj_custom_value = DBObject::retrieve_custom_value_by_object_id(Database::c_system_table_name, $curr_obj->object_type_id, constant('META_VALUE_KEY_TABLE_NAME'));

						//custom value is changed to description
                        $curr_obj->table_name = (null === $obj_custom_value ? null : $obj_custom_value->description);
						//$curr_obj->table_name = (null === $obj_custom_value ? null : $obj_custom_value->title);

                    }
                    break;
            }
			
            return $curr_obj;
		}
		
		
		//CRUD
		
		//Create
		public static function create($table_name, $slug, $title, $description, $object_type_id) {
			
			$dbh = Database::connect();
			$sth = $dbh->prepare('INSERT INTO `'.$table_name.'` (slug, title, description, object_type_id) VALUES (:slug, :title, :description, :object_type_id)');
			$sth->bindParam(':slug', $slug);
			$sth->bindParam(':title', $title);
			$sth->bindParam(':description', $description);
			$sth->bindParam(':object_type_id', $object_type_id);
			$sth->execute();
			
			return $dbh->lastInsertId();
			
		}
		
		public static function create_relationship($table_name, $object_type_id, $primary_id, $secondary_id) {
				
			return DBObject::create_full_object($table_name, '', '', '', $object_type_id, $primary_id, $secondary_id);
				
		}
				
		private static function create_full_object($table_name, $slug, $title, $description, $object_type_id, $primary_id, $secondary_id) {
			$dbh = Database::connect();
			
			$statement  = '';
			//$statement .= 'INSERT INTO `'.$table_name.'` (slug, title, description, object_type_id, relationship_type_id, primary_id, secondary_id) VALUES ';
			//$statement .= '(:slug, :title, :description, :object_type_id, :relationship_type_id, :primary_id, :secondary_id)';
			$statement .= 'INSERT INTO `'.$table_name.'` (slug, title, description, object_type_id, primary_id, secondary_id) VALUES ';
			$statement .= '(:slug, :title, :description, :object_type_id, :primary_id, :secondary_id)';
			
			$sth = $dbh->prepare($statement);
			$sth->bindParam(':slug', $slug);
			$sth->bindParam(':title', $title);
			$sth->bindParam(':description', $description);
			$sth->bindParam(':object_type_id', $object_type_id);
			//$sth->bindParam(':relationship_type_id', $relationship_type_id);
			$sth->bindParam(':primary_id', $primary_id);
			$sth->bindParam(':secondary_id', $secondary_id);
			$sth->execute();
				
			return $dbh->lastInsertId();
			
		}
		
		//Retrieve
		public static function retrieve_by_id($table_name, $id, $include_is_deleted_objects=false) {
			
			$data_row = DBObject::retrieve_row_by_id($table_name, $id, $include_is_deleted_objects);
			
			if (null === $data_row) {

				return null;
				
			} else {
			
				return DBObject::create_or_load_obj_with_data($data_row);
				
			}
			
		}
		
		public static function retrieve_row_by_id($table_name, $id, $include_is_deleted_objects=false) {

			$query = 'SELECT * FROM `'.$table_name.'` WHERE id=:id ';
			
			if (false === $include_is_deleted_objects) {
				$query .= 'AND is_deleted = 0 ';
			}
			
			$dbh = Database::connect();
			$sth = $dbh->prepare($query);
			$sth->bindParam(':id', $id);
			$sth->execute();
			
			if ($row = $sth->fetch()) {
				
				//$result = new Object();
				//$result->set_data($row);
				//$result = DBObject::create_or_load_obj_with_data($row);
				
				//return $result;
				return $row;
				
			} else {
				
				return null;
				
			}
				
		}
		
		public static function retrieve_by_values($table_name, $slug, $title, $description, $object_type_id=null, $return_first_item=false) {

			return DBObject::retrieve_by_values_in_detail($table_name, $slug, $title, $description, $object_type_id, $return_first_item);
			
		}
			
		public static function retrieve_by_values_in_detail($table_name, $slug, $title, $description, $object_type_id, $return_first_item, $offset=0, $retrieve_row_count=0, $include_is_deleted_objects=false, $count=false) {
		
			if ( ! $count ) {
				$query_fields = '*';
			} else {
				$query_fields = 'COUNT(*)';
			}
		
			if (null === $object_type_id) {
				$query = 'SELECT ' . $query_fields . ' FROM `'.$table_name.'` WHERE slug LIKE :slug AND title LIKE :title AND description LIKE :description ';
			} else {
				$query = 'SELECT ' . $query_fields . ' FROM `'.$table_name.'` WHERE slug LIKE :slug AND title LIKE :title AND description LIKE :description AND object_type_id=:object_type_id ';
			}
						
			if (false === $include_is_deleted_objects) {
				$query .= 'AND is_deleted = 0 ';
			}
			
			$query .= 'ORDER BY creation_timestamp DESC ';
			
			if ($retrieve_row_count > 0) {
				
				$query .= 'LIMIT ' . $retrieve_row_count . ' OFFSET ' . $offset . ' '; 

			}

			$dbh = Database::connect();
			$sth = $dbh->prepare($query);
			$sth->bindParam(':slug', $slug);
			$sth->bindParam(':title', $title);
			$sth->bindParam(':description', $description);
			
			if (null !== $object_type_id) {
				$sth->bindParam(':object_type_id', $object_type_id);
			}
						
			$sth->execute();
			
			//return row count
			if ($count) {
				
				if ($row = $sth->fetch()) {
					
					return $row[0];
					
				} else {
					
					//error
					throw new Exception('no value is returned for row count');
					
				}
				
			}
		
			$result = array();
						
			while ($row = $sth->fetch()) {
				
				$result_obj = DBObject::create_or_load_obj_with_data($row);

				array_push($result, $result_obj);
				
				if ($return_first_item)
					return $result[0];
				
			}
			
			if (0 === count($result))
				return null;
			else
				return $result;
		
		}

		//private static function retrieve_relationships_by_values($table_name, $object_type_id, $relationship_type_id, $primary_id, $secondary_id, $return_first_item=true, $offset=0, $retrieve_row_count=0, $include_is_deleted_objects=false) {
		public static function retrieve_relationships_by_values($table_name, $object_type_id, $primary_id, $secondary_id, $return_first_item=false, $offset=0, $retrieve_row_count=0, $include_is_deleted_objects=false, $count=false) {
			
			if (empty($table_name)) {
				throw new \Exception('table_name is not set');
			}
			
			if ( ! $count ) {
				$query_fields = '*';
			} else {
				$query_fields = 'COUNT(*)';
			}
			
			$query  = '';
			$query .= 'SELECT ' . $query_fields . ' FROM `'.$table_name.'` WHERE ';
			
			$query_array = array();
			if ($object_type_id >= 0) array_push($query_array, 'object_type_id=:object_type_id');
			//if ($relationship_type_id >= 0) array_push($query_array, 'relationship_type_id=:relationship_type_id');
			if ($primary_id >= 0) array_push($query_array, 'primary_id=:primary_id');
			if ($secondary_id >= 0) array_push($query_array, 'secondary_id=:secondary_id');
			
			if (false === $include_is_deleted_objects) {
				array_push($query_array, 'is_deleted = 0');
			}
			
			$query .= implode(' AND ', $query_array);
			
			$query .= ' ORDER BY creation_timestamp DESC';
				
			if ($retrieve_row_count > 0) {
			
				$query .= ' LIMIT ' . $retrieve_row_count . ' OFFSET ' . $offset;
			
			}
			
			$dbh = Database::connect();
			$sth = $dbh->prepare($query);
			if ($object_type_id >= 0) $sth->bindParam(':object_type_id', $object_type_id);
			//if ($relationship_type_id >= 0) $sth->bindParam(':relationship_type_id', $relationship_type_id);
			if ($primary_id >= 0) $sth->bindParam(':primary_id', $primary_id);
			if ($secondary_id >= 0) $sth->bindParam(':secondary_id', $secondary_id);
				
			$sth->execute();
			
			//return row count
			if ($count) {
				
				if ($row = $sth->fetch()) {
					
					return $row[0];
					
				} else {
					
					//error
					throw new Exception('no value is returned for row count');
					
				}
				
			}
			
			$result = array();
			
			while ($row = $sth->fetch()) {
				
				$result_obj = DBObject::create_or_load_obj_with_data($row);
				
				array_push($result, $result_obj);
				
				if ($return_first_item) {
					return $result[0];
				}
			}

			if (0 === count($result)) {
				return null;
			} else {
				return $result;				
			}
		}
		
		// Update
		
		public static function update_object($table_name, $id, $slug, $title, $description, $object_type_id) {

			$dbh = Database::connect();
			$sth = $dbh->prepare('UPDATE `'.$table_name.'` SET slug=:slug, title=:title, description=:description, object_type_id=:object_type_id WHERE id=:id');
			$sth->bindParam(':id', $id);
			$sth->bindParam(':slug', $slug);
			$sth->bindParam(':title', $title);
			$sth->bindParam(':description', $description);
			$sth->bindParam(':object_type_id', $object_type_id);
			$sth->execute();
			
		}
		
		// Delete

		public static function delete_object($table_name, $id, $by_flag=true) {
			
			if ($by_flag) {
				
				DBObject::delete_object_by_flag($table_name, $id);
				
			} else {
				
				DBObject::delete_actual_object($table_name, $id);
				
			}
			
		}
		
		private static function delete_object_by_flag($table_name, $id) {
		
			$dbh = Database::connect();
			$sth = $dbh->prepare('UPDATE `'.$table_name.'` SET is_deleted=1 WHERE id=:id');
			$sth->bindParam(':id', $id);
			$sth->execute();
		
		}
		
		private static function delete_actual_object($table_name, $id) {
		
			$dbh = Database::connect();
			$sth = $dbh->prepare('DELETE FROM `'.$table_name.'` WHERE id=:id');
			$sth->bindParam(':id', $id);
			$sth->execute();
		
		}
		
		//
		//
		//
		//
		//
		
		//
		//
		// Object API
		//
		//
		
		//
		// Object Type
		//
		
		public static function get_base_object_type() {
			return DBObject::retrieve_by_values(Database::c_system_table_name, constant('BASE_OBJECT_TYPE_SLUG'), '%', '%', constant('BASE_OBJECT_TYPE_OBJECT_TYPE_ID'), true);
		}
		
		//
		// Object types records are stored in the system table. One less table will makes the system easier to maintain.
		//
		public static function get_object_type($slug) {
			
			return DBObject::retrieve_by_values(Database::c_system_table_name, $slug, '%', '%', DBObject::get_base_object_type()->id, true);
			
		}
		
		//
		// Get table_name of object_type in system_table 
		//
		public static function get_object_type_table_name($slug) {
			
			if (null === ($obj_type = DBObject::get_object_type($slug))) {
				return null;
			}
			
			return DBObject::get_object_type_table_name_by_id($obj_type->id);
			
		}
		
		public static function get_object_type_table_name_by_id($id) {
			
			if (null === ($custom_value_table_name = DBObject::retrieve_custom_value_by_object_id(Database::c_system_table_name, $id, constant('META_VALUE_KEY_TABLE_NAME')))) {
				return null;
			}
				
			return $custom_value_table_name->description;
			
		}
		
		
		
		public static function get_object_relationship_type_table_name($slug) {
					
			if (null === ($obj_relationship_type = DBObject::get_object_relationship_type($slug))) {
				return null;
			}
			
			if (null === ($custom_value_table_name = DBObject::retrieve_custom_value_by_object_id(Database::c_system_table_name, $obj_relationship_type->id, constant('META_VALUE_KEY_TABLE_NAME')))) {
				return null;
			}
				
			return $custom_value_table_name->description;
				
		}
		
		//
		// Object types records are stored in the system table. One less table will makes the system easier to maintain.
		//
		// All object type id fields in various tables are pointing to the system table. System table won't be overloaded if
		// it is used wisely.
		//
		// Object types that are stored in the system table are:
		//   1. Object Type
		//   2. Object Relationship Type
		//   3. Object Custom Value Type
		//
		public static function register_new_object_type($slug, $obj_table_name=null) {
			
			if (null !== $obj_table_name) {
				$the_obj_table_name = $obj_table_name;
			} else {
				$the_obj_table_name = $slug;
			}
			
			if (constant('BASE_OBJECT_TYPE_SLUG') === $slug) {
			
				//creating base_object_type
				$base_object_type_id = constant('BASE_OBJECT_TYPE_OBJECT_TYPE_ID');				
				
			} else {
				
				if (null === ($object_type = DBObject::retrieve_by_values(Database::c_system_table_name, constant('BASE_OBJECT_TYPE_SLUG'), '%', '%', null, true))) {

					// create the base object type "object type" in the system table
					
					$base_object_type_id = DBObject::register_new_object_type(constant('BASE_OBJECT_TYPE_SLUG'), Database::c_system_table_name);
					
					$obj_base_object_type = ObjectQuery::retrieve(array('table_name' => Database::c_system_table_name, 'id' => $base_object_type_id));
					
					$obj_base_object_type->add_custom_value_with_table_name(Database::c_system_table_name, constant('META_VALUE_KEY_TABLE_NAME'), Database::c_system_table_name);
					
				} else {
				
					$base_object_type_id = $object_type->id;
					
				}
				
				//Search for the object type
				
				if (null !== ($existing_object_type = DBObject::retrieve_by_values(Database::c_system_table_name, $slug, '%', '%', $base_object_type_id, true))) {
					
					return $existing_object_type->id;
					
				}
			
			}
			
			//
			// Object types records are stored in the system table. Less tables will makes the system easier to maintain.
			//
			$object_type_id = DBObject::create(Database::c_system_table_name, $slug, '', '', $base_object_type_id);
			
			$obj_object_type = ObjectQuery::retrieve(array('table_name' => Database::c_system_table_name, 'id' => $object_type_id));
			
			switch ($slug) {
				case constant('BASE_OBJECT_TYPE_SLUG'):
				case constant('BASE_CUSTOM_VALUE_TYPE_SLUG'):
				case constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'):
					break;
					
				default:
					//
					//Ignore object type for custom_value and object_relationship_type
					//because they are supposed to be stored in the object type custom table
					//
					switch ($obj_object_type->slug) {
						case constant('BASE_CUSTOM_VALUE_TYPE_SLUG'):
						case constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'):
							break;
						default:
							DBObject::add_custom_value_by_object_id(Database::c_system_table_name, $obj_object_type->id, constant('META_VALUE_KEY_TABLE_NAME'), $the_obj_table_name);
							Database::create_object_table($the_obj_table_name);
							break;
					}
			}
			
			return $object_type_id;
		}

		//
		// Object Relationship
		//

		public static function register_new_relationship($first_object_type, $second_object_type, $obj_relationship_type_table_name=null) {
			
			if ( ( ! empty($first_object_type) ) && ( ! empty($second_object_type) ) ) {
				
				return DBObject::register_new_object_relationship_type($first_object_type . '_' . $second_object_type, $obj_relationship_type_table_name);
				
			}
			
		}

		public static function register_new_object_relationship_type($slug, $obj_relationship_type_table_name=null) {
			
			if (null !== $obj_relationship_type_table_name) {
				$the_obj_table_name = $obj_relationship_type_table_name;
			} else {
				$the_obj_table_name = $slug;
			}
			
			if (null === ($object_relationship_type = DBObject::retrieve_by_values(Database::c_system_table_name, constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'), '%', '%', null, true))) {
					
				//creating base_object_type
				$base_object_relationship_type_id = DBObject::register_new_object_type(constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'), Database::c_system_table_name);
			
			} else {
			
				$base_object_relationship_type_id = $object_relationship_type->id;
			
				if (null !== ($existing_object_relationship_type = DBObject::retrieve_by_values(Database::c_system_table_name, $slug, '%', '%', $base_object_relationship_type_id, true))) {
						
					return $existing_object_relationship_type->id;
						
				}
					
			}
			
			//Check if object relationship type has already been created
			if (null !== ($object_relationship_type_obj = DBObject::get_object_relationship_type($slug))) {
				return $object_relationship_type->id;
			}
				
			$new_object_relationship_type_id = DBObject::create(Database::c_system_table_name, $slug, '', '', $base_object_relationship_type_id);
			
			DBObject::add_custom_value_by_object_id(Database::c_system_table_name, $new_object_relationship_type_id, constant('META_VALUE_KEY_TABLE_NAME'), $the_obj_table_name);
			
			Database::create_object_table($the_obj_table_name);
			
			return $new_object_relationship_type_id;
			
		}
		
		public static function get_base_object_relationship_type() {
			return DBObject::get_object_type(constant('BASE_OBJECT_RELATIONSHIP_TYPE_SLUG'));
		}
		
		public static function get_object_relationship_type($slug) {
			return DBObject::retrieve_by_values(Database::c_system_table_name, $slug, '%', '%', DBObject::get_base_object_relationship_type()->id, true);
		}
		
		//Custom Value
		
		public static function get_custom_value_object_type_id() {

			if (null !== ( $ary_custom_value =  ObjectQuery::retrieve(array('table_name' => Database::c_system_table_name, 'slug' => constant('BASE_CUSTOM_VALUE_TYPE_SLUG'))) )) {

				return $ary_custom_value[0]->id;
				
			} else {
				
				return DBObject::register_new_object_type( constant('BASE_CUSTOM_VALUE_TYPE_SLUG'), Database::c_system_table_name );
				
			}
			
		}

		// Create
		public static function add_custom_value_by_object_id($custom_value_table_name, $object_id, $key, $value) {
			
			//if (0 !== count(DBObject::retrieve_custom_value_by_object_id($custom_value_table_name, $object_id, $key))) {
            if (null !== ($obj_custom_value = DBObject::retrieve_custom_value_by_object_id($custom_value_table_name, $object_id, $key))) {

				DBObject::update_custom_value_by_object_id($custom_value_table_name, $object_id, $key, $value);
				
			} else {
				
				$custom_value_object_type_id = DBObject::get_custom_value_object_type_id();

				DBObject::create_full_object($custom_value_table_name, $key, '', $value, $custom_value_object_type_id, $object_id, 0);
				
			}
			
		}
		
		// Retrieve
		
		public static function retrieve_custom_values_by_object_id($custom_value_table_name, $object_id) {
				
			$custom_value_object_type_id = DBObject::get_custom_value_object_type_id();
		
			//set the table_name of custom_value object to custom_value_table_name.
			return DBObject::retrieve_relationships_by_values($custom_value_table_name, $custom_value_object_type_id, $object_id, -1, false);
				
		}
		
		public static function retrieve_custom_value_by_object_id($custom_value_table_name, $object_id, $key) {
				
			if (null === ($custom_values = DBObject::retrieve_custom_values_by_object_id($custom_value_table_name, $object_id))) {
				return null;
			}
		
			foreach ($custom_values as $custom_value) {
		
				if ($key == $custom_value->slug) {
						
					return $custom_value;
						
				}
		
			}
				
			return null;
				
		}
		
		// Retrieve custom value objects by custom value

		public static function retrieve_custom_value_objects_by_custom_value($custom_value_table_name, $key, $value) {

			$custom_value_object_type_id = DBObject::get_custom_value_object_type_id();

			return DBObject::retrieve_by_values($custom_value_table_name, $key, '%', $value, $custom_value_object_type_id);

		}

		// Update
		public static function update_custom_value_by_object_id($custom_value_table_name, $object_id, $key, $value) {
			
			if (null === ObjectQuery::retrieve(array('table_name'=> $custom_value_table_name, 'id' => $object_id))) {
				
				throw new \Exception('Cannot find object (id='.$object_id.')');
				
			} else {
				
				if (null === ($custom_value = DBObject::retrieve_custom_value_by_object_id($custom_value_table_name, $object_id, $key))) {
					
					DBObject::add_custom_value_by_object_id($custom_value_table_name, $object_id, $key, $value);
					
				} else {
					
					$custom_value->table_name = $custom_value_table_name;
					$custom_value->description = $value;
					$custom_value->update();
					
				}
				
			}
				
		}
		
		// Delete
		public static function delete_custom_values_by_object_id($custom_value_table_name, $object_id) {
			
			$count = 0;
			
			if (null === ($custom_values = DBObject::retrieve_custom_values_by_object_id($custom_value_table_name, $object_id)))
				return 0;
			
			foreach ($custom_values as $custom_value) {
				
				$custom_value->table_name = $custom_value_table_name;
				$custom_value->delete();
				
				$count++;
				
			}
			
			return $count;
			
		}
		
		public static function delete_custom_value_by_object_id($custom_value_table_name, $object_id, $key) {

			if (null !== ($custom_value = DBObject::retrieve_custom_value_by_object_id($custom_value_table_name, $object_id, $key))) {
			
				$custom_value->table_name = $custom_value_table_name;
				$custom_value->delete();
				return 1;
			
			} else {
			
				return 0;
			
			}
		}
			
	}
	
	class Object {
		
		public $table_name = null;
		public $id;
		public $slug;
		public $title;
		public $description;
		public $object_type_id;
		//public $relatioship_type_id;
		public $primary_id;
		public $secondary_id;
		public $is_deleted;
		public $object_type_slug;

		public function __construct($slug=null, $title=null, $description=null, $object_type_slug=null) {
			
			$this->slug = $slug;
			$this->title = $title;
			$this->description = $description;
			$this->object_type_slug = $object_type_slug;
			
		}
		
// 		public function associate_relationship_with_table_name($table_name, $slug, $target_id) {
		//
		// $target_id is child
		//
		public function associate_relationship($slug, $target_id) {
			
			$object_relationship_type = DBObject::get_object_relationship_type($slug);
			
			if (null === $object_relationship_type) {
				throw new \Exception('Object Relationship Type ('.$slug.') is not present');
			} else {
				$object_relationship_type_id = $object_relationship_type->id;
			}
			
			$custom_value = DBObject::retrieve_custom_value_by_object_id(Database::c_system_table_name, $object_relationship_type->id, constant('META_VALUE_KEY_TABLE_NAME'));
			
			//custom value is changed to description
			$table_name_for_object_relationship_type = $custom_value->description;
			//$table_name_for_object_relationship_type = $custom_value->title;
			
			if (null !== ($relationship_object = DBObject::retrieve_relationships_by_values($table_name_for_object_relationship_type, $object_relationship_type_id, $this->id, $target_id))) {
				
				return $relationship_object->id;
				
			} else {
				
				return DBObject::create_relationship($table_name_for_object_relationship_type, $object_relationship_type_id, $this->id, $target_id);
				
			}
			
		}
				
		public function disassociate_relationship($slug, $target_id) {

			$relationships = $this->get_all_relationship_children_items($slug);
			
			foreach ($relationships as $relationship) {
				
				if ($target_id == $relationship->secondary_id) {
					
					$relationship->delete();
					return;
					
				}
				
			}
			
		}
		
		public function get_all_relationship_children_items($relationship_slug, $offset=0, $retrieve_row_count=0, $include_is_deleted_objects=false, $count=false) {
			
			if (null === ($object_relationship_type = DBObject::get_object_relationship_type($relationship_slug))) {
				throw new \Exception('Object Relationship Type ('.$relationship_slug.') is not present');
			}
			
			return DBObject::retrieve_relationships_by_values(DBObject::get_object_relationship_type_table_name($relationship_slug)
															, $object_relationship_type->id, $this->id, -1, false, $offset, $retrieve_row_count, $include_is_deleted_objects, $count);
			
		}
		
		public function get_all_relationship_parent_items($relationship_slug, $offset=0, $retrieve_row_count=0, $include_is_deleted_objects=false, $count=false) {
				
			if (null === ($object_relationship_type = DBObject::get_object_relationship_type($relationship_slug))) {
				throw new \Exception('Object Relationship Type ('.$relationship_slug.') is not present');
			}
			
			return DBObject::retrieve_relationships_by_values(DBObject::get_object_relationship_type_table_name($relationship_slug)
															, $object_relationship_type->id, -1, $this->id, false, $offset, $retrieve_row_count, $include_is_deleted_objects, $count);
				
		}
		
		//
		//
		//
		
		//
		// CRUD
		//
				
		public function create() {
		
			$object_type = DBObject::get_object_type($this->object_type_slug);
				
			if (null === $object_type) {
				throw new \Exception('Object Type is not present');
			}
					
			$object_table_name = DBObject::get_object_type_table_name($this->object_type_slug);
				
			$this->id = DBObject::create($object_table_name, $this->slug, $this->title, $this->description, $object_type->id);
			
			$data_row = DBObject::retrieve_row_by_id($object_table_name, $this->id);
			
			if (null !== $data_row) {
				
				return DBObject::create_or_load_obj_with_data($data_row, null, $this);
				
			}
			
		}
		
		public function update() {
			
			//
			// For the time being, the table_name is fixed for object types with dynamic table
			//
			if (isset($this->table_name) && (null !== $this->table_name)) {
				
				$table_name = $this->table_name;
				
			} else {
			
				if (null === ($table_name = DBObject::get_object_type_table_name_by_id($this->object_type_id))) {
					
					if (null === ($table_name = DBObject::get_object_type_table_name_by_id($this->primary_id))) {
						
						throw new \Exception('table_name does not exist');
						
					}
					
				}
			
			}
			
			DBObject::update_object($table_name, $this->id, $this->slug, $this->title, $this->description, $this->object_type_id);
			
		}
		
		public function delete() {

			//
			// For the time being, the table_name is fixed for object types with dynamic table
			//
			if (isset($this->table_name) && (null !== $this->table_name)) {
				
				$table_name = $this->table_name;
				
			} else {
			
				if (null === ($table_name = DBObject::get_object_type_table_name_by_id($this->object_type_id))) {
					
					if (null === ($table_name = DBObject::get_object_type_table_name_by_id($this->primary_id))) {
						
						throw new \Exception('table_name does not exist');
						
					}
					
				}
			
			}
			
			DBObject::delete_object($table_name, $this->id);
			
		}
		
		//
		// Custom values (Column Based Values)
		//
		public function get_custom_value_table_name() {

			//
			//We use the table name of the object by default.
			//
			//table_name is initialized to be null, so we use slug of the object as the table name if the object does not contains a custom table_name.
			//
			if (null !== $this->table_name) {
				return $this->table_name;
			} else {
				return $this->slug;
			}
		}
		
		// Create
		public function add_custom_value($key, $value) {

			DBObject::add_custom_value_by_object_id($this->get_custom_value_table_name(), $this->id, $key, $value);
				
		}
		
		public function add_custom_value_with_table_name($table_name, $key, $value) {
			
			DBObject::add_custom_value_by_object_id($table_name, $this->id, $key, $value);
			
		}
		
		// Retrieve
		public function retrieve_custom_values() {

            //Custom value of custom value is not allowed. Always return null.
            if (DBObject::get_object_type(constant('BASE_CUSTOM_VALUE_TYPE_SLUG'))->id == $this->object_type_id) {
                return null;
            }

			return DBObject::retrieve_custom_values_by_object_id($this->get_custom_value_table_name(), $this->id);
			
		}
		
		public function retrieve_custom_value($key) {

            //Custom value of custom value is not allowed. Always return null.
            if (DBObject::get_object_type(constant('BASE_CUSTOM_VALUE_TYPE_SLUG'))->id == $this->object_type_id) {
                return null;
            }

			return DBObject::retrieve_custom_value_by_object_id($this->get_custom_value_table_name(), $this->id, $key);
				
		}
		
		
		
		// Update
		public function update_custom_value($key, $value) {
				
			//var_dump($this->get_custom_value_table_name());
			
			//DBObject::update_custom_value_by_object_id($this->get_custom_value_table_name(), $this->get_custom_value_table_name(), $this->id, $key, $value);
			DBObject::update_custom_value_by_object_id($this->get_custom_value_table_name(), $this->id, $key, $value);
				
		}
		
		public function update_custom_value_with_table_names($custom_value_table_name, $key, $value) {
		
			DBObject::update_custom_value_by_object_id($custom_value_table_name, $this->id, $key, $value);
		
		}
		
		// Delete
		public function delete_custom_values() {
				
			return DBObject::delete_custom_values_by_object_id($this->get_custom_value_table_name(), $this->id);
				
		}
		
		public function delete_custom_values_with_table_name($custom_value_table_name) {
		
			return DBObject::delete_custom_values_by_object_id($custom_value_table_name, $this->id);
		
		}
		
		public function delete_custom_value($key) {
		
			return DBObject::delete_custom_value_by_object_id($this->get_custom_value_table_name(), $this->id, $key);
		
		}
		
		public function delete_custom_value_with_table_name($custom_value_table_name, $key) {
		
			return DBObject::delete_custom_value_by_object_id($custom_value_table_name, $this->id, $key);
		
		}
		
	}
	
	class ObjectQuery {
		
		public static function objCount($args) {

			$args['count'] = true;

			return ObjectQuery::retrieve($args);			
			
		}
		
		public static function retrieve($args) {

			//
			// For the case where only object_name is set 
			// 
			if ( isset($args['object_type_name']) && ( ! isset($args['table_name']) ) ) {
				
				$args['table_name'] = $args['object_type_name'];
				
			}
				
			if (isset($args['table_name']) && isset($args['id'])) {
					
				return DBObject::retrieve_by_id($args['table_name'], $args['id']);
		
			} else {
		
				if ( ( isset($args['object_type_name']) ) && ( ! isset($args['object_type_id']) ) ) {
					
					if ( null !== ($objectTypeObj = DBObject::get_object_type($args['object_type_name'])) ) {
						
						$args['object_type_id'] = $objectTypeObj->id; 
						
					}
					
				}
		
				if (isset($args['exact_match']) && (true === $args['exact_match'])) {
						
					$match_character = '';
						
				} else {
						
					$match_character = '%';
						
				}
		
				if ( ! isset($args['table_name']) ) {
					throw new \Exception('Table_name is not provided.');
				}
		
				return DBObject::retrieve_by_values_in_detail(
						$args['table_name']
						,empty($args['slug']) ? '%' : $match_character . $args['slug'] . $match_character
						,empty($args['title']) ? '%' : $match_character . $args['title'] . $match_character
						,empty($args['description']) ? '%' : $match_character . $args['description'] . $match_character
						,empty($args['object_type_id']) ? null : $args['object_type_id']
						,empty($args['return_first_item']) ? false : $args['return_first_item']
						,empty($args['offset']) ? 0 : $args['offset']
						,empty($args['retrieve_row_count']) ? 0 : $args['retrieve_row_count']
						,empty($args['include_deleted_objects']) ? false : $args['include_deleted_objects']
						,empty($args['count']) ? false : $args['count']
						);
		
			}
			
		}

		public static function retrieve_by_custom_value($object_type, $key, $value, $retrieve_actual_obj_instead_of_just_ID=true, $table_name=null) {

			$table_name_for_retrieval = $object_type;

			if (null !== $table_name) {

				$table_name_for_retrieval = $table_name;

			}

			$custom_value_objs = DBObject::retrieve_custom_value_objects_by_custom_value($table_name_for_retrieval, $key, $value);

			if (null === $custom_value_objs) {

				return null;

			} else {

				$result = array();

				foreach ($custom_value_objs as $custom_value_obj) {
					
					if ($retrieve_actual_obj_instead_of_just_ID) {

						if (null !== ($obj = ObjectQuery::retrieve(array('table_name'=>$object_type, 'id'=>$custom_value_obj->primary_id)))) {

							array_push($result, $obj);

						}

					} else {

						array_push($result, $custom_value_obj->primary_id);

					}

				}

				if (0 === count($result)) {

					return null;

				} else {

					return $result;

				}
				
			}

		}
		
	}
?>