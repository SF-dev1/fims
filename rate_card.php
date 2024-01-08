<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';

if ($_SERVER['HTTP_HOST'] != 'www.skmienterprise.com') {
    $update = false;
    $debug = true;
    echo '<pre>';
}
// $update = false;

if (isset($_GET['test']) && $_GET['test'] == 1) {
    $update = true;
    $debug = true;
    echo "<pre>";
} else {
    ignore_user_abort(true); // optional

    // Buffer all upcoming output...
    ob_start();

    // Send your response.
    echo ('Successfully Triggered RATE CARD SCRIPT');

    // Get the size of the output.
    $size = ob_get_length();

    // Disable compression (in case content length is compressed).
    header("Content-Encoding: none\r\n");

    // Set the content length of the response.
    header("Content-Length: {$size}");

    // Close the connection.
    header("Connection: close\r\n");

    // Flush all output.
    ob_end_flush();
    ob_flush();
    flush();
}

include_once(dirname(__FILE__) . '/config.php');
include_once(ROOT_PATH . '/functions.php');
include_once(ROOT_PATH . '/includes/connectors/flipkart/flipkart.php');
include_once(ROOT_PATH . '/includes/connectors/flipkart/flipkart-dashboard.php');

$date = date('Y-m-d');
// $date = date('2022-12-26');
$marketplace = 'flipkart';
$category = urlencode('Watches, Clocks & Accessories');
$vertical = 'watch';
$vertical_category = 'watches';
// $tier = 'diamond';
// $fulfilment = 'NON_FBF';
$brand = '';

// $dates = getBetweenDates('2022-09-01', date('Y-m-d'));
// var_dump($dates);
// exit;

$tiers = array("diamond", "platinum", "gold", "silver", "bronze", "wood");
$fulfilments = array('NON_FBF', 'FBF');
$accounts = $accounts['flipkart'];
$selected_account = $accounts[0];
$fk = new flipkart_dashboard($selected_account);
$GLOBALS['log_to'] = 'fk-rate-card';

// foreach ($dates as $date){
//     $log_to = 'fk-rate-card-'.$date;
foreach ($tiers as $tier) {
    foreach ($fulfilments as $fulfilment) {
        $url = 'https://seller.flipkart.com/napi/rate-card/fetchRateCardFees?service_profile=' . $fulfilment . '&date=' . $date . '&category=' . $category . '&vertical=' . $vertical . '&brand=' . $brand . '&partner_context=' . $marketplace . '&is_seller_dashboard=true&darwin_tier=' . $tier . '&shipping=false&sellerId=' . $selected_account->seller_id;
        $response = $fk->send_request($url);
        $log->write("\nURL: " . $url . "\nResponse: " . $response, $log_to);
        $response = json_decode($response, true);

        foreach ($response as $fees => $fees_value) {
            $log->write($fees, $log_to);
            echo $fees . '<br />';
            if ($fees == 'platformFee' || $fees == "closingFee" || $fees == "shippingFee" || $fees == "reverseShippingFee") {
                foreach ($fees_value['response'][$tier][$fulfilment] as $min_max => $min_max_value) {
                    $min_amount = $min_max;
                    $max_amount = '9999.99';
                    if (!is_int($min_max)) {
                        list($min_amount, $max_amount) = explode('-', $min_max);
                    }
                    foreach ($min_max_value['columns'] as $rate_type => $rate_value) {
                        if ($fees == 'platformFee') {
                            $db->where('fee_name', $fees);
                            $db->where('fee_tier', $tier);
                            $db->where('fee_category', $vertical_category);
                            $db->where('fee_marketplace', $marketplace);
                            $db->where('fee_attribute_min', number_format($min_amount, 2, '.', ''));
                            $db->where('fee_attribute_max', number_format($max_amount, 2, '.', ''));
                            $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
                            if (!empty($brand)) {
                                $db->where('fee_brand', $brand);
                            }
                            $our_value = $db->getOne(TBL_MP_FEES);

                            if ($our_value) {
                                if ($our_value['fee_value'] == $rate_value['value']) {
                                    $log->write('Same rate', $log_to);
                                    echo 'Same rate <br />';
                                } else {
                                    $mismatch_content = "mismatch found " . $our_value['fee_value'] . " // " . $rate_value['value'] . "\n" . $fees . " :: " . $tier . " :: " . $vertical_category . " :: " . $marketplace . " :: " . $min_amount . " :: " . $max_amount . " :: " . $date . " :: QUERY: " . $db->getLastQuery();
                                    $log->write($mismatch_content, $log_to);
                                    echo $mismatch_content . '<br />';
                                    update_rate_card($our_value, $rate_value['value'], $date);
                                }
                            } else {
                                $card = array(
                                    "fee_name" => $fees,
                                    "fee_tier" => $tier,
                                    "fee_brand" => "all",
                                    "fee_category" => $vertical_category,
                                    "fee_fulfilmentType" => $fulfilment,
                                    "fee_attribute_min" => $min_amount,
                                    "fee_attribute_max" => $max_amount,
                                    "fee_column" => $rate_type,
                                    "fee_type" => "percentage",
                                    "fee_value" => $rate_value['value'],
                                    "fee_constant" => (is_null($min_max_value['attributes']['constant']) ? "" : $min_max_value['attributes']['constant']),
                                    "fee_marketplace" => $marketplace,
                                    "start_date" => $date,
                                    "end_date" => "2025-12-31"
                                );

                                if (!empty($brand)) {
                                    $card['fee_brand'] = $brand;
                                }

                                if ($insert = $db->insert(TBL_MP_FEES, $card)) {
                                    $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
                                    echo $insert_data . ' <br />';
                                    $log->write($insert_data, $log_to);
                                }
                            }
                        }

                        if ($fees == 'closingFee') {
                            $db->where('fee_name', $fees);
                            $db->where('fee_tier', $tier);
                            $db->where('fee_marketplace', $marketplace);
                            $db->where('fee_fulfilmentType', $fulfilment);
                            $db->where('fee_attribute_min', number_format($min_amount, 2, '.', ''));
                            $db->where('fee_attribute_max', number_format($max_amount, 2, '.', ''));
                            $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
                            $our_value = $db->getOne(TBL_MP_FEES);

                            if ($our_value) {
                                if ($our_value['fee_value'] == $rate_value['value']) {
                                    $log->write('Same rate', $log_to);
                                    echo 'Same rate <br />';
                                } else {
                                    $mismatch_content = "mismatch found " . $our_value['fee_value'] . " // " . $rate_value['value'] . "\n" . $fees . " :: " . $tier . " :: " . $vertical_category . " :: " . $marketplace . " :: " . $min_amount . " :: " . $max_amount . " :: " . $date . " :: QUERY: " . $db->getLastQuery();
                                    $log->write($mismatch_content, $log_to);
                                    echo $mismatch_content . '<br />';
                                    update_rate_card($our_value, $rate_value['value'], $date);
                                }
                            } else {
                                $card = array(
                                    "fee_name" => $fees,
                                    "fee_tier" => $tier,
                                    "fee_brand" => "all",
                                    "fee_category" => "all",
                                    "fee_fulfilmentType" => $fulfilment,
                                    "fee_attribute_min" => $min_amount,
                                    "fee_attribute_max" => $max_amount,
                                    "fee_column" => '',
                                    "fee_type" => "fixed",
                                    "fee_value" => $rate_value['value'],
                                    "fee_constant" => (is_null($min_max_value['attributes']['constant']) ? "" : $min_max_value['attributes']['constant']),
                                    "fee_marketplace" => $marketplace,
                                    "start_date" => $date,
                                    "end_date" => "2025-12-31"
                                );

                                if ($insert = $db->insert(TBL_MP_FEES, $card)) {
                                    $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
                                    echo $insert_data . ' <br />';
                                    $log->write($insert_data, $log_to);
                                }
                            }
                        }

                        if ($fees == 'shippingFee') {
                            if ($max_amount == '0.00') {
                                $min_amount = $max_amount;
                                $max_amount = '20.00';
                            }

                            $db->where('fee_name', $fees);
                            $db->where('fee_tier', $tier);
                            $db->where('fee_fulfilmentType', $fulfilment);
                            $db->where('fee_column', $rate_type);
                            $db->where('fee_marketplace', $marketplace);
                            $db->where('fee_attribute_min', number_format($min_amount, 2, '.', ''));
                            $db->where('fee_attribute_max', number_format($max_amount, 2, '.', ''));
                            $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
                            $our_value = $db->getOne(TBL_MP_FEES);

                            if ($our_value) {
                                if ($our_value['fee_value'] == $rate_value['value']) {
                                    $log->write('Same rate', $log_to);
                                    echo 'Same rate <br />';
                                } else {
                                    $mismatch_content = "mismatch found " . $our_value['fee_value'] . " // " . $rate_value['value'] . "\n" . $fees . " :: " . $tier . " :: " . $vertical_category . " :: " . $marketplace . " :: " . $min_amount . " :: " . $max_amount . " :: " . $date . " :: QUERY: " . $db->getLastQuery();
                                    $log->write($mismatch_content, $log_to);
                                    echo $mismatch_content . '<br />';
                                    update_rate_card($our_value, $rate_value['value'], $date);
                                }
                            } else {
                                $card = array(
                                    "fee_name" => $fees,
                                    "fee_tier" => $tier,
                                    "fee_brand" => "all",
                                    "fee_category" => "all",
                                    "fee_fulfilmentType" => $fulfilment,
                                    "fee_attribute_min" => $min_amount,
                                    "fee_attribute_max" => $max_amount,
                                    "fee_column" => $rate_type,
                                    "fee_type" => "fixed",
                                    "fee_value" => $rate_value['value'],
                                    "fee_constant" => (is_null($min_max_value['attributes']['constant']) ? "" : $min_max_value['attributes']['constant']),
                                    "fee_marketplace" => $marketplace,
                                    "start_date" => $date,
                                    "end_date" => "2025-12-31"
                                );

                                if ($insert = $db->insert(TBL_MP_FEES, $card)) {
                                    $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
                                    echo $insert_data . ' <br />';
                                    $log->write($insert_data, $log_to);
                                }
                            }
                        }
                    }
                }
            } else if ($fees == "collectionFee" || ($fulfilment == "FBF" && $fees == "storageRemovalFee")) {
                foreach ($fees_value['response'][$tier] as $min_max => $min_max_value) {
                    $min_amount = $min_max;
                    $max_amount = '9999.99';
                    if (!is_int($min_max)) {
                        list($min_amount, $max_amount) = explode('-', $min_max);
                    }
                    foreach ($min_max_value['columns'] as $rate_type => $rate_value) {
                        if ($fees == 'collectionFee') {
                            $db->where('fee_name', $fees);
                            $db->where('fee_tier', $tier);
                            $db->where('fee_marketplace', $marketplace);
                            $db->where('fee_column', $rate_type);
                            $db->where('fee_attribute_min', number_format($min_amount, 2, '.', ''));
                            $db->where('fee_attribute_max', number_format($max_amount, 2, '.', ''));
                            $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
                            $our_value = $db->getOne(TBL_MP_FEES);

                            if ($our_value) {
                                if ($our_value['fee_value'] == $rate_value['value']) {
                                    $log->write('Same rate', $log_to);
                                    echo 'Same rate <br />';
                                } else {
                                    $mismatch_content = "mismatch found " . $our_value['fee_value'] . " // " . $rate_value['value'] . "\n" . $fees . " :: " . $tier . " :: " . $vertical_category . " :: " . $marketplace . " :: " . $min_amount . " :: " . $max_amount . " :: " . $date . " :: QUERY: " . $db->getLastQuery();
                                    $log->write($mismatch_content, $log_to);
                                    echo $mismatch_content . '<br />';
                                    update_rate_card($our_value, $rate_value['value'], $date, $rate_value['type']);
                                }
                            } else {
                                $card = array(
                                    "fee_name" => $fees,
                                    "fee_tier" => $tier,
                                    "fee_brand" => "all",
                                    "fee_category" => "all",
                                    "fee_fulfilmentType" => 'all',
                                    "fee_attribute_min" => $min_amount,
                                    "fee_attribute_max" => $max_amount,
                                    "fee_column" => $rate_type,
                                    "fee_type" => $rate_value['type'],
                                    "fee_value" => $rate_value['value'],
                                    "fee_constant" => '',
                                    "fee_marketplace" => $marketplace,
                                    "start_date" => $date,
                                    "end_date" => "2025-12-31"
                                );

                                if ($insert = $db->insert(TBL_MP_FEES, $card)) {
                                    $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
                                    echo $insert_data . ' <br />';
                                    $log->write($insert_data, $log_to);
                                }
                            }
                        }
                    }
                }
            } else if ($fulfilment == "FBF" && $fees == "pickAndPackFee") {
                foreach ($fees_value['response'][$tier] as $min_max => $min_max_value) {
                    $min_amount = $min_max;
                    $max_amount = '9999.99';
                    if (!is_int($min_max)) {
                        list($min_amount, $max_amount) = explode('-', $min_max);
                    }
                    foreach ($min_max_value['columns'] as $rate_type => $rate_value) {
                        if ($fees == 'pickAndPackFee') {
                            if (is_null($max_amount))
                                $max_amount = '20.00';

                            $db->where('fee_name', $fees);
                            $db->where('fee_tier', $tier);
                            $db->where('fee_marketplace', $marketplace);
                            $db->where('fee_attribute_min', number_format($min_amount, 2, '.', ''));
                            $db->where('fee_attribute_max', number_format($max_amount, 2, '.', ''));
                            $db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
                            $our_value = $db->getOne(TBL_MP_FEES);

                            if ($our_value) {
                                if ($our_value['fee_value'] == $rate_value['value']) {
                                    $log->write('Same rate', $log_to);
                                    echo 'Same rate <br />';
                                } else {
                                    $mismatch_content = "mismatch found " . $our_value['fee_value'] . " // " . $rate_value['value'] . "\n" . $fees . " :: " . $tier . " :: " . $vertical_category . " :: " . $marketplace . " :: " . $min_amount . " :: " . $max_amount . " :: " . $date . " :: QUERY: " . $db->getLastQuery();
                                    $log->write($mismatch_content, $log_to);
                                    echo $mismatch_content . '<br />';
                                    update_rate_card($our_value, $rate_value['value'], $date);
                                }
                            } else {
                                $card = array(
                                    "fee_name" => $fees,
                                    "fee_tier" => $tier,
                                    "fee_brand" => "all",
                                    "fee_category" => "all",
                                    "fee_fulfilmentType" => $fulfilment,
                                    "fee_attribute_min" => $min_amount,
                                    "fee_attribute_max" => $max_amount,
                                    "fee_column" => $rate_type,
                                    "fee_type" => "fixed",
                                    "fee_value" => $rate_value['value'],
                                    "fee_constant" => (is_null($min_max_value['attributes']['constant']) ? "" : $min_max_value['attributes']['constant']),
                                    "fee_marketplace" => $marketplace,
                                    "start_date" => $date,
                                    "end_date" => "2025-12-31"
                                );

                                if ($insert = $db->insert(TBL_MP_FEES, $card)) {
                                    $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
                                    echo $insert_data . ' <br />';
                                    $log->write($insert_data, $log_to);
                                }
                            }
                        }
                    }
                }
            }
            echo '<br />';
        }
        sleep(10);
    }
}
// }

function update_rate_card($card, $new_rate, $date, $rate_value_type = NULL)
{
    global $db, $log, $log_to;

    $update_details = array(
        'end_date' => date('Y-m-d', strtotime($date . ' -1 day')),
    );
    $db->where('fee_id', $card['fee_id']);
    if ($db->update(TBL_MP_FEES, $update_details)) {
        $log_data = 'Old rate card updated. ID: ' . $card['fee_id'] . ':: ' . json_encode($update_details) . '';
        $log->write($log_data, $log_to);
        echo $log_data . ' <br />';

        unset($card['fee_id']);
        unset($card['created_date']);
        $card['fee_value'] = $new_rate;
        $card['start_date'] = $date;
        if (!is_null($rate_value_type)) {
            $card['fee_type'] = $rate_value_type;
        }

        if ($card['fee_name'] == 'collectionFee')
            $card['fee_constant'] = '';

        if ($insert = $db->insert(TBL_MP_FEES, $card)) {
            $insert_data = 'New rate card added ' . $insert . ' :: ' . json_encode($card);
            echo $insert_data . ' <br />';
            $log->write($insert_data, $log_to);
        }
    }
}

function getBetweenDates($startDate, $endDate)
{
    $rangArray = [];

    $startDate = strtotime($startDate);
    $endDate = strtotime($endDate);

    for (
        $currentDate = $startDate;
        $currentDate <= $endDate;
        $currentDate += (86400)
    ) {

        $date = date('Y-m-d', $currentDate);
        $rangArray[] = $date;
    }

    return $rangArray;
}
exit;

// $db->where('fee_name', 'platformFee');
$db->where('fee_tier', $tier);
$db->where('(fee_category = ? OR fee_category = ?)', array($vertical, 'all'));
// $db->where('fee_brand', $brand);
// $db->where('(fee_attribute_min <= ? AND fee_attribute_max >= ? ) ', array($gross_price, $gross_price));
$db->where('(start_date <= ? AND end_date >= ? ) ', array($date, $date));
$db->where('fee_marketplace', $marketplace);
$fees = $db->get(TBL_MP_FEES);
// echo $db->getLastQuery().'<br />';
var_dump($fees);
exit;

echo '<table>';
echo '<tr><th>Fee Name</th><th>Tier</th><th>Brand</th><th>Category</th><th>Fulfilment Type</th><th>Min</th><th>Max</th><th>Column</th><th>Type</th><th>Value</th></tr>';
foreach ($fees as $fee) {
    echo '<tr>';
    echo '<td>' . $fee['fee_name'] . '</td>';
    echo '<td>' . $fee['fee_tier'] . '</td>';
    echo '<td>' . $fee['fee_brand'] . '</td>';
    echo '<td>' . $fee['fee_category'] . '</td>';
    echo '<td>' . $fee['fee_fulfilmentType'] . '</td>';
    echo '<td>' . $fee['fee_attribute_min'] . '</td>';
    echo '<td>' . $fee['fee_attribute_max'] . '</td>';
    echo '<td>' . $fee['fee_column'] . '</td>';
    echo '<td>' . $fee['fee_type'] . '</td>';
    echo '<td>' . $fee['fee_value'] . '</td>';
    echo '</tr>';
}
echo '</table>';
exit;


/*
global $accounts;
$options = "";

foreach ($accounts['flipkart'] as $account) {
    $options .= "<option value='".$account->account_id."'>".$account->fk_account_name."</option>";
}

$test = "";
if (isset($_GET['test']) && $_GET['test'] == "1")
    $test = "?test=1";
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Get Eligible Listings</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="assets/js/jquery.min.js"></script>
</head>
<body>
    <form method="post" action=rate_card.php<?php echo $test; ?>" name="form-update" enctype="multipart/form-data" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;">
        <table>
            <tr>
                <td>CSV File:</td>
                <td><input type="file" name="fileToUpload" id="fileToUpload"> OR </td>
                <td>FSN (comma seperated): <input type="text" name="fsn" placeholder="FSN (comma seperated)" id="fsn"></td>
            </tr><tr>
                <td>Account:</td>
                <td>
                    <select name="account_id">
                        <option value=""></option>
                        <?php echo $options; ?>
                    </select>
                </td>
            </tr><tr>
                <td>Discount Value:</td>
                <td><input type="number" name="discount" placeholder="Discount Value" value="" /><br /></td>
            </tr><tr>
                <td>Discount Type:</td>
                <td><input type="radio" name="discountType" value="percentage" id="discountPercentage" /><label for="discountPercentage">Percentage</label> OR <input type="radio" name="discountType" value="flat" id="discountFlat"/><label for="discountFlat">Flat</label></td>
            </tr><tr>
                <td>Incentive Value:</td>
                <td><input type="number" name="incentive" placeholder="Incentive" value="" />%<br /></td>
            </tr>
            </tr><tr>
                <td>Promotion Date:</td>
                <td><input type="date" name="startDate" placeholder="Promotion Start Date" value="" /> - <input type="date" name="endDate" placeholder="Promotion End Date" value="" /><br /></td>
            </tr><tr>
                <td><input type="submit" name="submit" value="Get Eligible Listings" /><br /></td>
            </tr>
        </table>
    </form>
</body>
</html>
<?php 
*/
