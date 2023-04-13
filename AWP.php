<?php
declare(strict_types=1);
/**
 * Plugin Name: Automatic Wordpress Posting from CSV
 * Description: Automatic Wordpress Posting from CSV
 * Plugin URI:  #
 * Author URI:  #
 * Author:      Hamza Siddique
 *
 *
 * Requires PHP: 5.4
 * Requires at least: 2.5
 *l
 *
 *
 * Version:     1.0.3
 */

if (!defined('ABSPATH')) {
    die();
}
define('AWP_PATH', plugin_dir_url(__FILE__));

require_once 'AWP_parse.php';
require_once 'AWP_SQL.php';

if(!class_exists('AWP')){
    class AWP_settings
    {
        public function hooks(){
            add_filter( 'cron_schedules', [$this, 'awp_cron_settings']);
            register_activation_hook(__FILE__, [$this, 'AWP_register']);
            add_action( 'admin_menu', [$this, 'AWP_pages']);
            add_action( 'wp_ajax_awp_upload_posts', [$this, 'AWP_response']);
            add_action( 'admin_print_footer_scripts', [$this, 'AWP_scripts']);
            add_action( 'awp_event', [$this, 'awp_cron']);
        }

        /**
         *
         * Function: AWP_register
         * Description: activation hook
         *
         */
        public function AWP_register(){
            AWP_SQL::instant();
            wp_clear_scheduled_hook('awp_event');
            wp_schedule_event(time(), 'awp_every_minute', 'awp_event');
        }

        /**
         *
         * Function: awp_cron_settings
         * Description: add cron settings
         *
         */
        public function awp_cron_settings($schedules){
            $schedules['awp_daily'] = array(
                    'interval'  => 86400,
                    'display'   => '24 hours',
            );

            $schedules['awp_every_minute'] = array(
                    'interval'  => 3600,
                    'display'   => 'Every 60 seconds',
            );

            return $schedules;
        }

        /**
         *
         * Function: awp_cron
         * Description: cron fire
         *
         */
        public function awp_cron(){
            global $wpdb;
            AWP_parse::doCron($wpdb);
        }

       /**
       * 
       * Function: AWP_pages
       * Description: Adds plugin page
       * 
       */
        public function AWP_pages(){
            add_menu_page('AWP CSV', 'AWP CSV', 'manage_options', 'awp_csv', [$this, 'AWP_PImport'], 'dashicons-database-import');
            add_submenu_page('awp_csv', 'AWP Stats', 'AWP Stats', 'manage_options', 'awp_stats', [$this, 'AWP_stats']);
            add_action( 'admin_enqueue_scripts', [$this, 'AWP_assets']);
        }

        /**
         *
         * Function: AWP_PImport
         * Description: loads page of settings
         *
         */
        public function AWP_PImport(){
            require_once 'templates/AWP_pageTemplate.php';
        }

        /**
         * Function: AWP_stats
         * @return void
         */
        public function AWP_stats(){
            require_once 'templates/AWP_stats.php';
        }

        /**
         *
         * Function: AWP_assets
         * Description: loads js and css
         *
         */
        public function AWP_assets(){
            wp_enqueue_style('awp', AWP_PATH.'assets/style.css');
            wp_enqueue_script('awp', AWP_PATH.'assets/vendor.js');
        }

        /**
         *
         * Function: AWP_response
         * Description: fire on ajax
         *
         */
        public function AWP_response(){
            //if( ! wp_verify_nonce( $_POST['nonce'], 'spu_wpnonce' ) ) die( 'Stop!');
            if(count($_FILES) > 2){
                echo json_encode('Please, do not hack');
                die();
            }
            $awp_template = $_POST['awp_template'] ?? die();
            $awp_title = $_POST['awp_title'] ?? die();
            $awp_term_id = $_POST['awp_term_id'] ?? '';
            $awp_row_per_day = is_numeric($_POST['awp_per_day']) ? $_POST['awp_per_day'] : 0;
            $files = $_FILES;
            foreach ($files as $file){
                if ($file['name'] != ''){
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    if ($ext == 'csv'){
                        $csv = $this->uploadCSV($file);
                    }else if(in_array($ext, array('png','jpg','jpeg'))){
                        $thumb_id = $this->upload_media($file);
                    }
                }
            }

            if (!isset($csv)){
                echo json_encode("Please, upload another CSV");
                die();
            }
            if (!isset($thumb_id)){
                $thumb_id = "";
            }
            $row = array(
                'awp_thumb'         => array(
                    'san_type'  => 'int',
                    'value'     => ($thumb_id == "") ? 0 : $thumb_id,
                    ),
                'awp_row'           => array(
                    'san_type'  => 'string',
                    'value'     => 1 . '|' . $csv['awp_total_rows'],
                ),
                'awp_template'      => array(
                    'san_type'  => 'text',
                    'value'     => $awp_template,
                ),
                'awp_title'         => array(
                    'san_type'  => 'text',
                    'value'     => $awp_title,
                ),
                'awp_csv'           => array(
                    'san_type'  => 'text',
                    'value'     => $csv['awp_csv'],
                ),
                'awp_csv_fields'    => array(
                    'san_type'  => 'array',
                    'value'     => $csv['awp_csv_fields'],
                ),
                'awp_term_id'      => array(
                    'san_type'  => 'int',
                    'value'     =>  intval($awp_term_id),
                ),
                'awp_per_day'       => array(
                    'san_type'  => 'int',
                    'value'     => $awp_row_per_day,
                ),
                'awp_processed_today'  => array(
                    'san_type'  => 'int',
                    'value'     => 0,
                ),
                'awp_last_run'  => array(
                    'san_type'  => 'string',
                    'value'     => strtotime(date("d-m-Y")),
                ),
            );
            AWP_SQL::setData($row);
            echo json_encode("Your data is saved");
            die();
        }

        /**
         *
         * Function: upload_media
         * Description: upload media in library
         *
         */
        private function upload_media($file){
            require_once ABSPATH . "wp-load.php";
            require_once ABSPATH . "wp-admin/includes/file.php";
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $upload = wp_handle_upload($file, array('test_form' => FALSE));
            if(!empty($upload['error'])){
                return 0;
            }
            $attachment_id = wp_insert_attachment(array(
                    'guid'              => $upload['url'],
                    'post_mime_type'    => $upload['type'],
                    'post_title'        => basename( $upload['file'] ),
                    'post_content'      => '',
                    'post_status'       => 'publish',
            ), $upload['file']);

            if (is_wp_error($attachment_id) || !$attachment_id){
                return 0;
            }

            wp_update_attachment_metadata($attachment_id,
                wp_generate_attachment_metadata($attachment_id, $upload['file']));
            return $attachment_id;
        }

        /**
         *
         * Function: uploadCSV
         * Description: upload csv file
         *
         */
        public function uploadCSV($file){
            $file_name = basename($file['name'], '.csv');
            $upload_file = dirname(__FILE__) . '/files/'.$file_name.'.csv';

            if(!move_uploaded_file($file['tmp_name'], $upload_file)){
                echo json_encode("Please, upload another CSV");
                die();
            }
            global $wpdb;
            $fields = AWP_parse::scanCSV($wpdb, $file_name, 'getLabels');
            $count_of_rows = AWP_parse::scanCSV($wpdb, $file_name, 'getCounts');
            if (!isset($fields)){
                echo json_encode("Please, upload another CSV");
                die();
            }
            return array('awp_csv'=>$file_name, 'awp_csv_fields'=>$fields, 'awp_total_rows'=>$count_of_rows);
        }


        /**
         *
         * Function: AWP_scripts
         * Description: jQuery script for AJAX
         *
         */
        public function AWP_scripts(){
            ?>
            <script>
            jQuery(document).ready(function () {
                jQuery("form#awp_form").submit(function(e){
                    e.preventDefault();
                    let formData = new FormData(jQuery(this)[0]);
                    jQuery.ajax({
                        type: "POST",
                        url: ajaxurl,
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: "json",
                        success: function(data) {
                            jQuery('#awp_result p').html(data);
                        },
                        error: function (data){
                            jQuery('#awp_result p').html(data);
                        }
                    });
                });
            });
            </script>
<?php
        }
    }

    $AWP = new AWP_settings();
    $AWP->hooks();
}