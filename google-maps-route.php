<?php
/*
  Plugin Name: Google Maps Route Plugin
  Description: Google Maps Route is an open source solution built for Wordpress to display several locations along a route on Google Maps.
  Version: 1.0.2
  Author: NetMadeEz
  Author URI: http://netmadeez.com/
  Plugin URI: http://netmadeez.com/blog/google-maps-route-plugin/
 */
 
global $gmr_is_script_included;
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
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
    if (!empty($wpdb->charset))
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    if (!empty($wpdb->collate))
        $charset_collate .= " COLLATE $wpdb->collate";

    $sql = "CREATE TABLE {$table_gmaps} (
                            id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            `gmaps_address` longtext,
                            `gmaps_title` varchar(200),
                            `gmaps_description` longtext,
                            `gmaps_url` varchar(200) ,
                            `gmaps_lat_log` varchar(50),
                            `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            ) {$charset_collate};";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

if ($_REQUEST['page'] === 'nme-list-gmaps-page' && is_admin()) {
    wp_enqueue_script('nme-gmaps-admin', NME_GMP_PLUGIN_URL . '/js/nme-gmaps-admin.js', array('jquery'));
}

add_shortcode('route', 'google_map_route_shortcode');

/**
 * Google Maps Shortcode function
 */
function google_map_route_shortcode($atts, $content=NULL) {
    if (get_option('nme_gmaps_apikey')) {
        global $wpdb, $table_gmaps, $gmr_is_script_included, $id;
        $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
        $sql = "SELECT * from {$table_gmaps}";
        $sql_results = $wpdb->get_results($sql, ARRAY_A);
        $gmr_id = 'gmr_id_' . $id;
        extract(shortcode_atts( array(
                'height' => '500',
                'width' => '640',
                'title' => ''
                ), $atts));
        $h = $height;
        $w = $width;

        $points = array();
        $marker = '';
        $i = 1;
        foreach ($sql_results as $result) {
            $data = $result['gmaps_title'] . '<br/><small>' . substr($result['gmaps_description'], 0, 50) . '...</small>';
            $value = explode(',', $result['gmaps_lat_log']);
            $lat = $value[0];
            $lng = $value[1];
            $points[] = 'new GLatLng(' . $lat . ',' . $lng . ')';
            $marker.= 'bounds.extend(new GLatLng(' . $lat . ',' . $lng . '));';
            $marker.= 'var pt_' . $i . '=new GLatLng(' . $lat . ',' . $lng . ');';
            $marker.='var markerManager' . $i . '= new GMarker(pt_' . $i . ');';
            $marker.= 'var html' . $i . ' = "' . $data . '";';
            $marker.='GEvent.addListener(markerManager' . $i . ',"mouseover",function(){
                      markerManager' . $i . '.openInfoWindowHtml(html' . $i . ');});';
            $marker.='GEvent.addListener(markerManager' . $i . ', "mouseout", function() {
                      markerManager' . $i . '.closeInfoWindow(html' . $i . ');});';
            $marker.='GEvent.addListener(markerManager' . $i . ', "click", function() {
                      window.location = "' . $result['gmaps_url'] . '";});';
            $marker.='map.addOverlay(markerManager' . $i . ');';
            $i++;
        }
        $points = implode(',', $points);

        $output = '';
        $output.='<div id="' . $gmr_id . '" style="width:' . $w . 'px;height:' . $h . 'px"></div>';

        $output.='<script type="text/javascript">';
        $output.='var map = new GMap2(document.getElementById("' . $gmr_id . '"));';
        $output.='map.addControl(new GMapTypeControl(true));';
        $output.='map.addControl(new GSmallZoomControl());';
        $output.= 'var bounds = new GLatLngBounds;';

        $output.= $marker;
        $output.='map.setCenter(bounds.getCenter());';
        $output.='var minZoom = Math.min(map.getBoundsZoomLevel(bounds), 11);';
        $output.='map.setZoom(minZoom);';
        $output.='map.setMapType(G_PHYSICAL_MAP); ';
        $output.='var points =[' . $points . '];';
        $output.='var polyline = new GPolyline(points, "' . get_option('nme_marker_color') . '", ' . get_option('nme_marker_width') . ', ' . get_option('nme_marker_transparent') . ');';
        $output.='map.addOverlay(polyline);';
        $output.='</script>';
        if (!$gmr_is_script_included) {
            $content .= '<script src="http://maps.google.com/maps?file=api&v=2&sensor=true&key=' . get_option('nme_gmaps_apikey') . '" type="text/javascript"></script>';
            $gmr_is_script_included = true;
        }
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
    }
    return $content;
}

?>