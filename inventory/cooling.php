<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<?php
include "../includes/class-mysqlidb.php";

$server = "fims.cy9ut4zgz1ip.us-east-1.rds.amazonaws.com";
$username = "root";
$password = "Ishan576!";
$database = "bas";

$db = new MysqliDb($server, $username, $password, $database);

$db->join("bas_inventory_log l", "l.inv_id = i.inv_id");
$db->where("i.inv_status", "qc_cooling");
$db->groupBy("i.ctn_id");
$db->orderBy("MAX(l.invi_id)");
$cartoons = $db->get("bas_inventory i", null, "i.ctn_id");
?>

<body>
    <table border="1" width="100%">
        <tr>
            <th>Cartoon Id</th>
            <th>Inventory Ids</th>
            <th>Inventory Count</th>
        </tr>
        <?php
        $mcount = 0;
        foreach ($cartoons as $cartoon) {
            $count = 0;
        ?>
        <tr>
            <td><?= "CTN" . sprintf("%9d", $cartoon["ctn_id"]) ?></td>
            <td>
                <?php
                    $db->join("bas_products_master pm", "pm.pid = i.item_id");
                    $db->where("i.ctn_id", $cartoon["ctn_id"]);
                    $db->where("i.inv_status", "qc_cooling");
                    $invIds = $db->get("bas_inventory i", null, "i.inv_id, pm.sku");
                    foreach ($invIds as $inv) {
                        echo $inv["inv_id"] . " - " . $inv["sku"] . "<br>";
                        $count++;
                        $mcount++;
                    } ?>
            </td>
            <td><?= $count ?></td>
        </tr>
        <?php
        }
        ?>
    </table>
    <h1><?= $mcount ?></h1>
</body>

</html>