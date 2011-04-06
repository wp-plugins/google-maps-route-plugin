<?php
/*
 * Add Google Maps Route Location Page
 */

function nme_gmaps_page() {
    global $wpdb, $table_gmaps;
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
    if (isset($_POST) && $_POST['nme-save-gmaps'] === "Save GMaps") {
        $address = trim($_POST['nme-address']);
        $title = $_POST['nme-title'];
        $description = $_POST['nme-desc-gmaps'];
        $nme_url = $_POST['nme-url-gmaps'];

        $url = "http://maps.google.com/maps/geo?q=" . urlencode($address) . "&output=csv&key=" . $nme_apikey;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        if (substr($data, 0, 3) == "200") {
            $data = explode(",", $data);
            $precision = $data[1];
            $latitude = $data[2];
            $longitude = $data[3];

            $result_id = $wpdb->insert(
                            $table_gmaps,
                            array(
                                'gmaps_address' => $address,
                                'gmaps_title' => $title,
                                'gmaps_description' => $description,
                                'gmaps_url' => $nme_url,
                                'gmaps_lat_log' => $latitude . ',' . $longitude,
                            ),
                            array('%s', '%s', '%s', '%s', '%s'));
            if ($result_id) {
                echo '<div class="updated fade">Location Saved Successfully!!!</div>';
            } else {
                echo '<div class="error">Error Saving Data</div>';
            }
        } else {
            echo '<div class="error"><b>Http error ' . substr($data, 0, 3) . ' Error Saving Data</b></div>';
        }
    }
?>

    <div class="wrap">
        <h2>Add Location</h2>
        <form method="post" name="nme-add-gmaps-form">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="nme-address">Address</label></th>
                    <td><input type="text" name="nme-address" id="nme-address" size="62"><br /><span class="description">Hint : Submit the full location : number, street, city, country. For big cities and famous places, the country is optional. "Bastille Paris" or "Opera Sydney" will do.</span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="nme-title">Title</label></th>
                    <td><input id="nme-title" type="text" size="62" name="nme-title" /></td>
                </tr>
                <tr valign="top">
                    <th><label for="nme-desc-gmaps">Description</label></th>
                    <td><textarea id="nme-desc-gmaps" name="nme-desc-gmaps" rows="5" cols="55"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="nme-url-gmaps">URL</label></th>
                    <td><input type="text" value="" name="nme-url-gmaps" id="nme-url-gmaps" size="62"></td>
                </tr>
                <tr valign="top">
                    <td></td>
                    <td><input type="submit" value="Save Location" name="nme-save-gmaps" class="button-primary"></td>
                </tr>
            </table>
        </form>
    </div>
<?php
}

function nme_list_gmaps_page() {
    global $wpdb;
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
    if ($_REQUEST['page'] === 'nme-list-gmaps-page' && $_REQUEST['action'] === 'edit') {
        if (isset($_POST['nme-update-gmaps'])) {
            $address = trim($_POST['nme-address']);
            $title = $_POST['nme-title'];
            $description = $_POST['nme-desc-gmaps'];
            $nme_url = $_POST['nme-url-gmaps'];

            $url = "http://maps.google.com/maps/geo?q=" . urlencode($address) . "&output=csv&key=" . $nme_apikey;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $data = curl_exec($ch);
            curl_close($ch);
            
            if (substr($data, 0, 3) == "200") {
                $data = explode(",", $data);
                $precision = $data[1];
                $latitude = $data[2];
                $longitude = $data[3];

                $result_id = $wpdb->update(
                                $table_gmaps,
                                array(
                                    'gmaps_address' => $address,
                                    'gmaps_title' => $title,
                                    'gmaps_description' => $description,
                                    'gmaps_url' => $nme_url,
                                    'gmaps_lat_log' => $latitude . ',' . $longitude,
                                ),
                                array('id' => $_GET['id']),
                                array('%s', '%s', '%s', '%s', '%s'),
                                array('%d'));
                if ($result_id) {
                    echo '<div class="updated fade">Location Successfully Edited !!!</div>';
                } else {
                    echo '<div class="error">Error Saving Data</div>';
                }
            } else {
                echo '<div class="error"><b>Http error ' . substr($data, 0, 3) . ' Error Saving Data</b></div>';
            }
        }
        $id = $_GET['id'];
        $sql = "select * from {$table_gmaps} WHERE `id`={$id}";
        $sql_result = $wpdb->get_row($sql, ARRAY_A);
?>
        <div class ="wrap">
            <h2>Edit Location</h2>

            <form method="post" name="nme-edit-gmaps-form">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="nme-address">Address</label></th>
                        <td><input type="text" name="nme-address" id="nme-address" value="<?php echo $sql_result['gmaps_address'] ?>" size="80"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="nme-title">Title</label></th>
                        <td><input type="text" size="35" name="nme-title" id="nme-title" value="<?php echo $sql_result['gmaps_title'] ?>"></td>
                    </tr>
                    <tr valign="top">
                        <th><label for="nme-desc-gmaps">Description</label></th>
                        <td><textarea id="nme-desc-gmaps" name="nme-desc-gmaps" rows="5" cols="55"><?php echo $sql_result['gmaps_description'] ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th><label for="nme-url-gmaps">URL</label></th>
                        <td><input type="text" value="<?php echo $sql_result['gmaps_url'] ?>" name="nme-url-gmaps" id="nme-url-gmaps" size="35"></td>
                    </tr>
                    <tr valign="top">
                        <td></td>
                        <td><input type="submit" value="Update Location" name="nme-update-gmaps" class="button-primary"></td>
                    </tr>
                </table>
            </form>

        </div>
<?php
    } else {
?>
        <div class ="wrap">
            <h2>Location Listing</h2>
<?php
        $sql = "SELECT * from {$table_gmaps} ";
        $pagenum = isset($_GET['paged']) ? $_GET['paged'] : 1;
        $per_page = 20;
        $action_count = count($wpdb->get_results($sql));
        $total = ceil($action_count / $per_page);
        $action_offset = ($pagenum - 1) * $per_page;
        $page_links = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => ceil($action_count / $per_page),
                    'current' => $pagenum
                ));
        $sql .= " LIMIT {$action_offset}, {$per_page}";
        $gmaps_ids = $wpdb->get_results($sql);

        if (!empty($gmaps_ids)) {
            if ($page_links) {
?>
                <div class="tablenav">
                    <div class="tablenav-pages">
    <?php
                $page_links_text = sprintf('<span class="displaying-num">' . __('Displaying %s&#8211;%s of %s') . '</span>%s',
                                number_format_i18n(( $pagenum - 1 ) * $per_page + 1),
                                number_format_i18n(min($pagenum * $per_page, $action_count)),
                                number_format_i18n($action_count),
                                $page_links
                );
                echo $page_links_text;
    ?>
            </div>
        </div>
            <?php
            }
        }
            ?>
    <div class="clear"></div>
    <?php if (!empty($gmaps_ids)) {
 ?>
            <table class="widefat post fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th class="check-column" scope="row"></th>
                        <th>Location Address</th>
                        <th>Title</th>
                        <th class="column-title">Description</th>
                        <th>URL</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>        
<?php
            $i = 1;
            $count = (($pagenum-1)*$per_page)+1;
            foreach ($gmaps_ids as $gid) {

                if ($i % 2 == 0) {
                    echo '<tr class="alternate">';
                } else {
                    echo '<tr>';
                }
                echo '<td class="check-column">' . $count . '</td>';
                echo '<td>' . $gid->gmaps_address . '<p><a href="admin.php?page=nme-list-gmaps-page&action=edit&id=' . $gid->id . '">Edit</a> | <a class="remove-admin-gmaps" style="cursor:pointer" rel="' . $gid->id . '">Delete</a></p></td>';
                echo '<td>' . $gid->gmaps_title . '</td>';
                echo '<td>' . substr($gid->gmaps_description, 0, 50) . '...</td>';
                echo '<td>' . $gid->gmaps_url . '</td>';
                echo '<td>' . date($gid->created_date) . '</td>';
                echo '</tr>';
                $i++;
                $count++;
            }
?>
        </tbody>

    </table>
            <?php } else {
            echo 'No records found!!!';
        } ?>
</div>
<?php
    }
}

/*
 * API Key Settings Page
 */

function nme_settings_gmaps_page() {
    global $wpdb;
    if (isset($_POST['nme-save-apikey']) == 'Save Key') {
        update_option('nme_gmaps_apikey', $_POST['nme-gmap-api-key']);
        update_option('nme_marker_color', $_POST['nme-marker-color']);
        update_option('nme_marker_width', $_POST['nme-marker-width']);
        update_option('nme_marker_transparent', $_POST['nme-marker-trans']);

        echo '<div class="updated fade">Settings Successfully Saved</div>';
    }
    $gmaps_api_key = get_option('nme_gmaps_apikey');
    $gmaps_marker = get_option('nme_marker_color');
    $gmaps_marker_width = get_option('nme_marker_width');
    $gmaps_marker_transparent = get_option('nme_marker_transparent');
?>
    <div class="wrap">
        <h2>Google Maps Settings</h2>
<?php if (empty($gmaps_api_key)) {
 ?>
            <p>If you don't have a Google Maps API Key, <a target="_blank" href="http://www.google.com/apis/maps/signup.html">click here</a>.</p>
    <?php } ?>
    <form method="post">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="nme-gmap-api-key">Enter API Key</label></th>
                <td><input id="nme-gmap-api-key" type="text" size="45" value="<?php echo $gmaps_api_key; ?>" name="nme-gmap-api-key" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="nme-marker-color">Route Color</label></th>
                <td><input id="nme-marker-color" type="text" size="7" value="<?php echo $gmaps_marker; ?>" name="nme-marker-color" /><span class="description">6 digit hex color code. e.g.: #FFFFFF</span></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="nme-marker-width">Route Width</label></th>
                <td><input id="nme-marker-width" type="text" size="3" value="<?php echo $gmaps_marker_width; ?>" name="nme-marker-width" /><span class="description">Value between 1 to 10</span></td>
            <tr valign="top">
                <th scope="row"><label for="nme-marker-trans">Route Transparency</label></th>
                <td><input id="nme-marker-trans" type="text" size="3" value="<?php echo $gmaps_marker_transparent; ?>" name="nme-marker-trans" /><span class="description">Value between 0 to 1 including decimal value</span></td>
            </tr>
            <tr valign="top">
                <th></th>
                <td><input type="submit" value="Save Settings" name="nme-save-apikey" class="button-primary" /></td>
            </tr>
        </table>
    </form>
    <table class="form-table">
        <tr valign="top">
            <th scope="row">
                <strong>Shortcode:</strong>
            </th>
            <td>
                [route height="500" width="500"]<br/>
                <span class="description">height: Height which you want to assign to the Map.</span>
                <span class="description">width: Width which you want to assign to the Map.</span>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">
                <strong>Widget:</strong>
            </th>
            <td>
                <span class="description">Note: Your theme should be Widget enabled.</span><br/>
                <span class="description">Go to Appearance > Widgets.</span><br/>
                <span class="description">Find Widget "Google Maps Route". Drag and drop where you want it.</span><br/>
            </td>
        </tr>
    </table>
</div>

<?php
}

/*
 * Fucntion to get the latitude and longitute
 */

function getLatLong($id=FALSE) {
    global $wpdb, $table_gmaps;
    $latlong = array();
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
    $sql = "SELECT `gmaps_lat_log` from `{$table_gmaps}` WHERE id={$id}";
    $sql_r = $wpdb->get_var($sql);
    $latlong[] = explode(',', $sql_r);
    return $latlong;
}

function nme_delete_gmaps() {
    global $wpdb, $table_gmaps;
    $table_gmaps = $wpdb->base_prefix . 'nme_gmaps_data';
    $id = $_POST['id'];
    $sql = "DELETE FROM {$table_gmaps} WHERE id = {$id}";
    $sql_result = $wpdb->query($sql);
    if ($sql_result)
        echo 'true';
    die;
}

add_action('wp_ajax_nme_delete_gmaps', 'nme_delete_gmaps');
?>