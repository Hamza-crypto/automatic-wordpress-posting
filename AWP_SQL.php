<?php
declare( strict_types=1 );
global $wpdb;
if ( ! class_exists( 'AWP_SQL' ) ) {
	class AWP_SQL {
		private static $table_name = 'edg_appdata';

        /**
         *
         * Function: instant
         * Description: create table and columns in the beginning
         *
         */
		public static function instant()
        {
            global $wpdb;
            $wpdb->query('CREATE TABLE IF NOT EXISTS ' . self::$table_name . ' (
                id int NOT NULL AUTO_INCREMENT,
                awp_template longtext NOT NULL,
                awp_title varchar(255) NOT NULL,
                awp_row varchar(255) NOT NULL,
                awp_thumb int,
                awp_csv varchar(255) NOT NULL,
                awp_csv_fields varchar(255) NOT NULL,
                awp_term_id int,
                awp_per_day int,
                PRIMARY KEY (id)
            )');
            $wpdb->query('ALTER TABLE '.self::$table_name.' CHANGE awp_row awp_row varchar(255) NOT NULL');
            $wpdb->query('ALTER TABLE '.self::$table_name.' ADD COLUMN awp_processed_today int');
            $wpdb->query('ALTER TABLE '.self::$table_name.' ADD COLUMN awp_last_run int');

            $wpdb->query('CREATE TABLE IF NOT EXISTS test_csv (
                id int NOT NULL AUTO_INCREMENT,
                csv_name varchar(255) NOT NULL,
                info varchar(255) NOT NULL,
                PRIMARY KEY (id)
            )');
        }

        /**
         *
         * Function: setData
         * Description: create row with values
         *
         */
		public static function setData( array $args ) {
			$values  = array();
			$columns = array();
			$awp_csv_fields  = !is_array($args['awp_csv_fields']['value']) ? die() : $args['awp_csv_fields']['value'];

			foreach ($args as $label => $data){
                $columns[] = $label;
                if($data['san_type'] == 'int'){
                    $values[]  = "'".trim(esc_sql(filter_var( $data['value'], FILTER_SANITIZE_NUMBER_INT )))."'";
                }else if($data['san_type'] == 'array'){
                    $values[] = "'".trim(implode(':?:', esc_sql($awp_csv_fields)))."'";
                }else{
                    $values[]  = "'".trim(esc_sql($data['value']))."'";
                }
            }

            global $wpdb;
			$values_sql  = implode( ', ', $values );
			$columns_sql = implode( ', ', $columns );
			$wpdb->query( 'INSERT INTO ' . self::$table_name . ' (' . $columns_sql . ') VALUES (' . $values_sql . ')' );
		}

        /**
         *
         * Function: updateData
         * Description: update line int in $row['awp_row']
         *
         */
		public static function updateData( array $args, $wpdb ) {
            $awp_row      = isset( $args['awp_row'] ) ? esc_sql( intval($args['awp_row'])) : 0;
            $awp_csv      = isset( $args['awp_csv'] ) ? esc_sql( $args['awp_csv']) : 0;
            if (isset($args['counts'])){
                $awp_row .= '|'. esc_sql( intval($args['counts']));
            }
			echo  'UPDATE ' . self::$table_name . ' SET awp_row = "' . $awp_row . '" WHERE awp_csv = "' . $awp_csv . '"'. '<br>';
            $wpdb->query( 'UPDATE ' . self::$table_name . ' SET awp_row = "' . $awp_row . '" WHERE awp_csv = "' . $awp_csv . '"' );
		}

        public static function updateData2( array $args, $wpdb ) {
            $awp_row      = isset( $args['awp_row'] ) ? esc_sql( intval($args['awp_row'])) : 0;
            $awp_csv      = isset( $args['awp_csv'] ) ? esc_sql( $args['awp_csv']) : 0;
            $awp_processed_today = $args['awp_processed_today'];
            if (isset($args['counts'])){
                $awp_row .= '|'. esc_sql( intval($args['counts']));
            }
			echo  'UPDATE ' . self::$table_name . ' SET awp_row = "' . $awp_row . '" AND  awp_processed_today = "'. $awp_processed_today . '" WHERE awp_csv = "' . $awp_csv . '"' . '<br>';
            $wpdb->query( 'UPDATE ' . self::$table_name . ' SET awp_row = "' . $awp_row . '" , awp_processed_today = "'. $awp_processed_today . '" WHERE awp_csv = "' . $awp_csv . '"' );
		}

        public static function updateLastRun( array $args, $wpdb ) {
            $awp_processed_today = $args['awp_processed_today'];
            $awp_last_run = $args['awp_last_run'];
            $awp_csv = $args['awp_csv'];

//            echo  'UPDATE ' . self::$table_name . ' SET awp_processed_today = "' . $awp_processed_today . '" ,  awp_last_run = "'. $awp_last_run . '" WHERE awp_csv = "' . $awp_csv . '"' . '<br>';
            $wpdb->query( 'UPDATE ' . self::$table_name . ' SET awp_processed_today = "' . $awp_processed_today . '" ,  awp_last_run = "'. $awp_last_run . '" WHERE awp_csv = "' . $awp_csv . '"' );

 }

        public static function update_log( $file, $log,  $wpdb ) {

            echo  'INSERT INTO  test_csv ( csv_name , info ) VALUES ( "' . $file . '", "' . $log . '" )' . '<br>';
            $wpdb->query( 'INSERT INTO  test_csv ( csv_name , info ) VALUES ( "' . $file . '", "' . $log . '" )' );

        }


        /**
         *
         * Function: removeData
         * Description: remove row from table
         *
         */
        public static function removeData($filename, $wpdb){
            $filename = esc_sql($filename);
            return $wpdb->query('DELETE FROM '. self::$table_name . ' WHERE awp_csv = "'. $filename . '" ');
        }

        /**
         *
         * Function: getRows
         * Description: get all rows from table
         *
         */
		public static function getRows($wpdb) {
			return $wpdb->get_results( 'SELECT * FROM ' . self::$table_name, 'ARRAY_A');
		}
	}
}