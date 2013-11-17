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
            global $gmr_is_script_included, $wpdb, $table_gmaps;
            extract($args);
            $id = 'gmr_widget_' . $widget_id;
            $widget_id = str_replace('-', '_', $widget_id);
            extract(shortcode_atts( array(
                'height' => '300',
                'width' => '300',
                'title' => '',
                'disable_popup' => ''
                ), $instance));
            $h = !empty($height) ? $height : '200';
            $w = !empty($width) ? $width : '200';
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
            $center = $sql_results[0]['gmaps_lat_log'];
            echo $before_widget;
            if (!empty($title)) {
                echo $before_title . $title . $after_title;
            }
    ?>
            <div class="gmapswidget">
        <?php
            $output = '';
            if (!$gmr_is_script_included) {
                $output .= '<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>';
                $gmr_is_script_included = true;
            }
            $points = array();
            $output.='<div id="' . $id . '" style="width:' . $w . 'px;height:' . $h . 'px"></div>';
            $output.='<script type="text/javascript">';
            $output.='var map_'.$widget_id.', marker, markerLatlng, flightPath, flightPlanCoordinates, infoWindow;
                        function initialize_'.$widget_id.'() {
                          var mapOptions = {
                            zoom: 6,
                            center: new google.maps.LatLng('.$center.'),
                            mapTypeId: google.maps.MapTypeId.ROADMAP
                          };
                          map_'.$widget_id.' = new google.maps.Map(document.getElementById(\'' . $id . '\'),
                              mapOptions);
                        ';
            $i = count($sql_results)+1;
            foreach ($sql_results as $result) {
                $data = $result['gmaps_title'] . '<br><small>' . substr($result['gmaps_description'], 0, 50) . '...</small>';
                
                $value = explode(',', $result['gmaps_lat_log']);
                $lat = $value[0];
                $lng = $value[1];
                $points[] = 'new google.maps.LatLng(' . $lat . ',' . $lng . ')';
                $output .= 'var marker_'.$widget_id.'_'.$i.' = new google.maps.Marker({
                      position: new google.maps.LatLng(' . $lat . ',' . $lng . '),
                      map: map_'.$widget_id.',
                      title: \'' . $result['gmaps_title'] . '\'
                  });
                  infoWindow_'.$widget_id.'_'.$i.' = new google.maps.InfoWindow({
                    content: \'' . $data . '\'
                  });';
                if( $result['gmaps_url'] != ''){
                    $output .= 'google.maps.event.addListener(marker_'.$widget_id.'_'.$i.', "click", function() {
                                    window.location = \'' . $result['gmaps_url'] . '\';
                                });';
                }
                if ($disable_popup != 'checked') {
                      $output .= 'google.maps.event.addListener(marker_'.$widget_id.'_'.$i.', "mouseover", function() {
                                    infoWindow_'.$widget_id.'_'.$i.'.open(map_'.$widget_id.', marker_'.$widget_id.'_'.$i.');
                                  });
                                  google.maps.event.addListener(marker_'.$widget_id.'_'.$i.', "mouseout", function() {
                                        infoWindow_'.$widget_id.'_'.$i.'.close(map_'.$widget_id.', marker_'.$widget_id.'_'.$i.');
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
                          flightPath.setMap(map_'.$widget_id.');';
            $output .='var latlngbounds = new google.maps.LatLngBounds();
                       for ( var i = 0; i < flightPlanCoordinates.length; i++ ) {
                            latlngbounds.extend( flightPlanCoordinates[ i ] );
                       }
                       map_'.$widget_id.'.fitBounds( latlngbounds );';
            $output .='}';
            $output .='google.maps.event.addDomListener(window, \'load\', initialize_'.$widget_id.');';

            $output.='</script>';

            echo  $output;
            if (get_option('nme_link_back') === 'checked') {
                $display = '';
                if (get_option('nme_link_back_hidden') === 'checked') {
                    $display = 'display:none;';
                }
                echo '<style>
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
        ?>
        </div>
    <?php
            echo $after_widget;
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