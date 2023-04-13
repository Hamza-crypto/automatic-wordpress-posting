<?php

if (!class_exists('AWP_parse')) {
    class AWP_parse
    {
        /**
         *
         * Function: doCron
         * Description: fire on CRON
         *
         * If awp_last_run = today, then pick this record and check
         * if it has left some rows for today's processing
         * if yes, then process those remaining rows
         *
         * If today's processing is done, then update its awp_last_run
         * to tomorrow's date, so that it can be picked up on next CRON
         *
         */
        public static function doCron($wpdb)
        {
            $rows = AWP_SQL::getRows($wpdb);
            $rows_count = count($rows);
            if ($rows_count > 0) {
                set_time_limit(0);
                $today_date = strtotime(date("d-m-Y"));
                foreach ($rows as $key => $row) {

                    if ( $row['awp_last_run'] > $today_date){ // if awp_last_run is in future, then skip this record, because it has finished its execution for today
                        continue;
                    }
                    if ( $today_date == $row['awp_last_run'] && (int)$row['awp_processed_today'] < (int)$row['awp_per_day']) {
                        self::scanCSV($wpdb, $row['awp_csv'], '', $row/*$posts_per_csv*/);
                        break;
                    }
                    else{
                        $tomorrow = date("d-m-Y", strtotime("+1 day"));
                        $args = [
                            'awp_csv' => $row['awp_csv'],
                            'awp_processed_today' => 0,
                            'awp_last_run' => strtotime($tomorrow),
                            ];
                        AWP_SQL::updateLastRun($args, $wpdb);
                        print_r($args);
                    }

                }
            }
        }

        /**
         *
         * Function: scanCSV
         * Description: whole work with CSV
         *
         */
        public static function scanCSV($wpdb, string $file_name = '', string $type = '', array $row = array()/*, int $per_csv = 0*/)
        {
            if ($file_name == '' && $type != 'getLabels' && $type != 'getCounts') {
                die();
            }
            $upload_file = dirname(__FILE__) . '/files/' . $file_name . '.csv';
            if (file_exists($upload_file) && $handle = new SplFileObject($upload_file, 'r')) {
                if ($type != 'getLabels' && !empty($row)) {
                    if (preg_match('#|#', $row['awp_row']) > 0){
                        $row_points = explode('|', $row['awp_row']);
                        $awp_start_row = $row_points[0];
                        $counts =  $row_points[1];
                    }else{
                        $awp_start_row = $row['awp_row'];
                        $counts = self::scanCSV($wpdb, $file_name, 'getCounts');
                    }
                    $awp_thumb = $row['awp_thumb'];
                    $awp_term_id = $row['awp_term_id'] ?? "";
					if(isset($row['awp_per_day']) && $row['awp_per_day'] > 0 && is_numeric($row['awp_per_day'])){
						$awp_per_csv = $row['awp_per_day'] > 5000 ? 5000 : $row['awp_per_day'];
					}else{
						$awp_per_csv = 10;
					}
                    $awp_csv_fields = explode(':?:', $row['awp_csv_fields']);
                    $awp_finish_row = $awp_start_row + $awp_per_csv;
                    $awp_fields_count = count($awp_csv_fields);

                    if($awp_start_row != 1){
                        $awp_start_row++;
                    }
                    $handle->seek($awp_start_row);
                    $rows_count = 0;
                    while (FALSE !== $data = $handle->fgetcsv()) {
                        if ($handle->key() <= $awp_finish_row && !$handle->eof()) {
                            $awp_post_content = $row['awp_template'];
                            $awp_post_title = $row['awp_title'];
                            for ($subkey = 0; $subkey < $awp_fields_count; $subkey++) {
                                $awp_post_content = str_ireplace('{{' . $awp_csv_fields[$subkey] . '}}', $data[$subkey], $awp_post_content);
                                $awp_post_title = str_ireplace('{{' . $awp_csv_fields[$subkey] . '}}', $data[$subkey], $awp_post_title);
                            }

                            $post_id = wp_insert_post(array(
                                'post_content' => wpautop($awp_post_content),
                                'post_title' => $awp_post_title,
                                'post_status' => 'publish',
                            ));
                            AWP_SQL::update_log($file_name, sprintf('count: %d  -  handle: %d   -   level: %d   -  PostID: %d   - Time:%s', $rows_count, $handle->key(),  $data[0], $post_id, date("H:i:s d/m",time())),  $wpdb);
                            sleep(0.1);
                            if ($awp_term_id != "") {
                                wp_set_object_terms($post_id, (int)$awp_term_id, 'category');
                            }
                            if ($awp_thumb > 1) {
                                update_post_meta($post_id, '_thumbnail_id', $awp_thumb);
                            }
                        }else{
                            $current_row = $handle->key() - 1;

                            if($handle->eof()){
                                global $wpdb;
                                AWP_SQL::removeData($file_name, $wpdb);
                            }else{
                                $args = array(
                                    'awp_row' => $current_row,
                                    'awp_csv' => $file_name
                                );
                                $counts = self::scanCSV($wpdb, $file_name, 'getCounts');

                                $args['counts'] = $counts;

//								print_r($args);
                                AWP_SQL::updateData($args, $wpdb);
                            }
                            break;
                        }
                        $rows_count++;
                        if ($rows_count == 10) {
                            $current_row = $handle->key();

                            $args = array(
                                'awp_row' => $current_row,
                                'awp_csv' => $file_name,
                                'awp_processed_today' => $row['awp_processed_today'] + $rows_count,
                            );
                            if (isset($counts)){
                                $args['counts'] = $counts;
                            }
//                            print_r($args);
                            AWP_SQL::updateData2($args, $wpdb);
                            break;
                        }

                    }
                    return $awp_finish_row - $awp_start_row;
                }else if($type == 'getLabels'){
                    if (FALSE !== $data = $handle->fgetcsv()) {
                        $columns_count = count($data);
                        $fields = array();
                        for ($i = 0; $i < $columns_count; $i++) {
                            $fields[] = $data[$i];
                        }
                        $return = $fields;
                    } else {
                        $return = NULL;
                    }
                }else if($type == 'getCounts'){
                    $handle->seek($handle->getSize());
                    $return = $handle->key();
                }
            }
            return $return ?? NULL;
        }

    }


}