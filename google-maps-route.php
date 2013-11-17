<?php
/*
  Plugin Name: Google Maps Route Plugin
  Description: Google Maps Route is an open source a solution built for Wordpress to display several locations along a route on Google Maps.
  Version: 1.0.3
  Author: NetMadeEz
  Author URI: http://netmadeez.com/
  Plugin URI: http://netmadeez.com/blog/google-maps-route-plugin/
 */
global $gmr_is_script_included, $wpdb, $table_gmaps;
if(is_multisite () ){
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
}
else{
    $table_gmaps = $wpdb->prefix . 'nme_gmaps_data';
}
$gmr_is_script_included = FALSE;
require_once ('php/nme-admin-map.php');
require_once ('php/nme-gmaps-widget.php');

define('NME_GMP_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));
define('NME_GMP_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . dirname(plugin_basename(__FILE__)));

add_action('admin_menu', 'nme_administration_menu');
/*
 * Administration Menu
 */

function nme_administration_menu() {
    add_object_page('Google Maps Route', 'Map Routes', 'manage_options', 'nme-gmaps-page', '', NME_GMP_PLUGIN_URL . '/images/google-marker-gray.png');
    add_submenu_page('nme-gmaps-page', 'Add New Location', 'Add New Location', 'manage_options', 'nme-gmaps-page', 'nme_gmaps_page');
    add_submenu_page('nme-gmaps-page', 'List Locations', 'List Locations', 'manage_options', 'nme-list-gmaps-page', 'nme_list_gmaps_page');
    add_submenu_page('nme-gmaps-page', 'Settings', 'Settings', 'manage_options', 'nme-settings-gmaps-page', 'nme_settings_gmaps_page');
}

register_activation_hook(__FILE__, 'nme_gmaps_install');

/**
 * @global <type> $wpdb
 * @global string $table_gmaps
 */
function nme_gmaps_install() {
    global $wpdb, $table_gmaps;
    if( $wpdb->get_var("show tables like '$bulk_page_formate_table'") != $table_gmaps ){
        $sql = "CREATE TABLE `{$table_gmaps}` (
                            id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `gmaps_address` longtext,
                            `gmaps_title` varchar(200),
                            `gmaps_description` longtext,
                            `gmaps_url` varchar(200) ,
                            `gmaps_lat_log` varchar(50),
                            `order` int DEFAULT 0,
                            `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            );";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

function nme_route_map_admin_script(){
    if ( isset($_REQUEST['page']) && ($_REQUEST['page'] === 'nme-list-gmaps-page') ) {
        wp_register_script('nme-gmaps-admin', NME_GMP_PLUGIN_URL . '/js/nme-gmaps-admin.js', array('jquery'));
        wp_register_script('nme-gmaps-sortable', NME_GMP_PLUGIN_URL . '/js/jquery-sortable.js', array('jquery'));
        wp_register_style('nme-gmaps-admin-css', NME_GMP_PLUGIN_URL . '/css/nme-gmaps-admin.css');
        wp_enqueue_script('nme-gmaps-admin');
        wp_enqueue_script('nme-gmaps-sortable');
        wp_enqueue_style('nme-gmaps-admin-css');
    }
}

add_action('admin_enqueue_scripts', 'nme_route_map_admin_script');

add_shortcode('route', 'google_map_route_shortcode');

/**
 * Google Maps Shortcode function
 */
function google_map_route_shortcode($atts, $content=NULL) {
    global $wpdb, $table_gmaps, $gmr_is_script_included, $id;
    $sql = "SELECT * from {$table_gmaps}";
    $order = get_option('nme_gmaps_location_order');
    if(!is_array($order)){
        $order = array();
    }
    if( count($order) > 0 ){
        $order_str = implode(', ', $order);
        $sql .= " ORDER BY FIELD(id, {$order_str})";
    }
    $sql_results = $wpdb->get_results($sql, ARRAY_A);
    $gmr_id = 'gmr_id_' . $id;
    extract(shortcode_atts( array(
            'height' => '500',
            'width' => '640',
            'title' => ''
            ), $atts));
    $h = $height;
    $w = $width;
    $route_opacity  = get_option('nme_marker_transparent');
    $route_width    = get_option('nme_marker_width');
    $route_color    = get_option('nme_marker_color');
    if( !$route_opacity ){
        $route_opacity = '1.0';
    }
    if( !$route_width ){
        $route_width = '2';
    }
    if( !$route_color ){
        $route_color = '#FF0000';
    }
    $points = array();
    
    $center = $sql_results[0]['gmaps_lat_log'];

    $output = '';
    if (!$gmr_is_script_included) {
        $output .= '<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>';
        $gmr_is_script_included = true;
    }
    $output.='<div class="route-gmap" id="' . $gmr_id . '" style="width:' . $w . 'px;height:' . $h . 'px; "></div>';

    $output.='<script type="text/javascript">';
    $output.='function initialize_'.$id.'() {
                  var map_'.$id.', marker, markerLatlng, flightPath, flightPlanCoordinates, infoWindow;
                  var mapOptions = {
                    zoom: 8,
                    center: new google.maps.LatLng('.$center.'),
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                  };
                  map_'.$id.' = new google.maps.Map(document.getElementById(\'' . $gmr_id . '\'),
                      mapOptions);
                ';
    $i = 1;
    foreach ($sql_results as $result) {
        $data = $result['gmaps_title'] . '<br/><small>' . substr($result['gmaps_description'], 0, 50) . '...</small>';
        $value = explode(',', $result['gmaps_lat_log']);
        $lat = $value[0];
        $lng = $value[1];
        $points[] = 'new google.maps.LatLng(' . $lat . ',' . $lng . ')';
        $output .= 'var marker_'.$id.'_'.$i.' = new google.maps.Marker({
                      position: new google.maps.LatLng(' . $lat . ',' . $lng . '),
                      map: map_'.$id.',
                      title: \'' . $result['gmaps_title'] . '\'
                  });
                  infoWindow_'.$id.'_'.$i.' = new google.maps.InfoWindow({
                    content: \'' . $data . '\'
                  });
                  google.maps.event.addListener(marker_'.$id.'_'.$i.', "mouseover", function() {
                        infoWindow_'.$id.'_'.$i.'.open(map_'.$id.', marker_'.$id.'_'.$i.');
                  });
                  google.maps.event.addListener(marker_'.$id.'_'.$i.', "mouseout", function() {
                        infoWindow_'.$id.'_'.$i.'.close(map_'.$id.', marker_'.$id.'_'.$i.');
                  });';
        if( $result['gmaps_url'] != ''){
            $output .= 'google.maps.event.addListener(marker_'.$id.'_'.$i.', "click", function() {
                            window.location = \'' . $result['gmaps_url'] . '\';
                        });';
        }
        else{
            $output .= 'google.maps.event.addListener(marker_'.$id.'_'.$i.', "click", function() {
                            infoWindow_'.$id.'_'.$i.'.open(map_'.$id.', marker_'.$id.'_'.$i.');
                        });';
        }
        $i++;
    }
    $points_str = implode(', ', $points);
    $output .='flightPlanCoordinates = ['.$points_str.'];
               flightPath = new google.maps.Polyline({
                        path: flightPlanCoordinates,
                        strokeColor: \''.$route_color.'\',
                        strokeOpacity: '.$route_opacity.',
                        strokeWeight: '.$route_width.'
                  });
                  flightPath.setMap(map_'.$id.');';
    $output .='var latlngbounds = new google.maps.LatLngBounds();
               for ( var i = 0; i < flightPlanCoordinates.length; i++ ) {
                    latlngbounds.extend( flightPlanCoordinates[ i ] );
               }
               map_'.$id.'.fitBounds( latlngbounds );';
    $output .='}';
    $output .='google.maps.event.addDomListener(window, \'load\', initialize_'.$id.');';
    
    $output.='</script>';
    
    if (get_option('nme_link_back') === 'checked') {
        $display = '';
        if (get_option('nme_link_back_hidden') === 'checked') {
            $display = 'display:none;';
        }
        $output .= '<style>
                    span.nme_link_back {
                            font-style: italic;
                            position: relative;
                            font-size: 10px;
                            ' . $display . '
                    }
                    span.nme_link_back a {
                            color: #666;
                            display: inline-block;
                            line-height: 16px;
                            text-decoration: none;
                    }
                    span.nme_link_back a:hover {
                            text-decoration: underline;
                    }
              </style>
              <span class="nme_link_back"><a title="WordPress Plugin" href="http://netmadeez.com/">WordPress Plugin</a> by <a title="WordPress Plugin" href="http://netmadeez.com/">NetMadeEz</a></span>';
    }
        $content.= $output;
    return $content;
}
?>