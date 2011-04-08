<?php

function nme_gmaps_widget_init() {
    register_widget('WP_GMaps_Widget');
}

add_action('widgets_init', 'nme_gmaps_widget_init', 1);

class WP_GMaps_Widget extends WP_Widget {

    function WP_GMaps_Widget() {
        $widget_ops = array('classname' => 'widget_gmaps', 'description' => __('Google Maps Route Widget'));
        $this->WP_Widget('gmaps_widget', __('Google Maps Route'), $widget_ops);
    }

    function widget($args, $instance) {
        if (get_option('nme_gmaps_apikey')) {
            global $gmr_is_script_included, $wpdb;
            extract($args);
            $id = 'gmr_widget_' . $widget_id;
            extract(shortcode_atts( array(
                'height' => '300',
                'width' => '300',
                'title' => '',
                'disable_popup' => ''
                ), $instance));
            $h = !empty($height) ? $height : '200';
            $w = !empty($width) ? $width : '200';

            $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
            $sql = "SELECT * from {$table_gmaps}";
            $sql_results = $wpdb->get_results($sql, ARRAY_A);

            echo $before_widget;
            if (!empty($title)) {
                echo $before_title . $title . $after_title;
            }
    ?>
            <div class="gmapswidget">
        <?php
            $points = array();
            $marker = '';
            $i = count($sql_results)+1;
            foreach ($sql_results as $result) {
                if ($disable_popup != 'checked') {
                $data = $result['gmaps_title'] . '<br><small>' . substr($result['gmaps_description'], 0, 50) . '...</small>';
                }
                $value = explode(',', $result['gmaps_lat_log']);
                $lat = $value[0];
                $lng = $value[1];
                $points[] = 'new GLatLng(' . $lat . ',' . $lng . ')';
                $marker.= 'bounds.extend(new GLatLng(' . $lat . ',' . $lng . '));';
                $marker.= 'var pt_' . $i . '=new GLatLng(' . $lat . ',' . $lng . ');';
                $marker.='var markerManager' . $i . '= new GMarker(pt_' . $i . ');';
                if ($disable_popup != 'checked') {
                    $marker.= 'var html' . $i . ' = "' . $data . '";';
                    $marker.='GEvent.addListener(markerManager' . $i . ',"mouseover",function(){
                          markerManager' . $i . '.openInfoWindowHtml(html' . $i . ');});';
                    $marker.='GEvent.addListener(markerManager' . $i . ', "mouseout", function() {
                          markerManager' . $i . '.closeInfoWindow(html' . $i . ');});';
                }
                $marker.='GEvent.addListener(markerManager' . $i . ', "click", function() {
                      window.location = "' . $result['gmaps_url'] . '";});';
                $marker.='map.addOverlay(markerManager' . $i . ');';
                $i++;
            }
            $points = implode(',', $points);

            $output = '';
            $output.='<div id="' . $id . '" style="width:' . $w . 'px;height:' . $h . 'px"></div>';

            $output.='<script>';
            $output.='var map = new GMap2(document.getElementById("' . $id . '"));';
            $output.='map.addControl(new GMapTypeControl(true));';
            $output.='map.addControl(new GSmallZoomControl());';
            $output.= 'var bounds = new GLatLngBounds;';

            $output.= $marker;
            $output.='map.setCenter(bounds.getCenter());';
            $output.='var minZoom = Math.min(map.getBoundsZoomLevel(bounds), 5);';
            $output.='map.setZoom(minZoom);';
            $output.='map.setMapType(G_PHYSICAL_MAP); ';

            $output.='var points =[' . $points . '];';
            $output.='var polyline = new GPolyline(points, "' . get_option('nme_marker_color') . '", ' . get_option('nme_marker_width') . ', ' . get_option('nme_marker_transparent') . ');';
            $output.='map.addOverlay(polyline);';
            $output.='</script>';

            if (!$gmr_is_script_included) {
                echo '<script src="http://maps.google.com/maps?file=api&v=2&sensor=true&key=' . get_option('nme_gmaps_apikey') . '" type="text/javascript"></script>';
                $gmr_is_script_included = true;
            }
            echo  $output;
        ?>
        </div>
    <?php
            echo $after_widget;
        }
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['height'] = strip_tags($new_instance['height']);
        $instance['width'] = strip_tags($new_instance['width']);
        $instance['disable_popup'] = strip_tags($new_instance['disable_popup']);

        return $instance;
    }

    function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => '', 'height' => '', 'width' => '', 'disable_popup' =>  'checked'));
        $title = strip_tags($instance['title']);
        $height = strip_tags($instance['height']);
        $width = strip_tags($instance['width']);
        $disabled_popup = strip_tags($instance['disable_popup']);
?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>

        <p><label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height: (in px)'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo esc_attr($height); ?>" size="3" /></p>

        <p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width: (in px)'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo esc_attr($width); ?>" size="3" /></p>

        <p><label for="<?php echo $this->get_field_id('disable_popup'); ?>"><?php _e('Disable Info Pop Up:'); ?></label>
            <input id="<?php echo $this->get_field_id('disable_popup'); ?>" name="<?php echo $this->get_field_name('disable_popup'); ?>" type="checkbox" value="checked" <?php echo esc_attr($disabled_popup);?> /></p>
<?php
    }

}
?>