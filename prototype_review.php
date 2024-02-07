<?php
include "includes/class-mysqlidb.php";
$db = new MysqliDb("fims.cy9ut4zgz1ip.us-east-1.rds.amazonaws.com", "root", "Ishan576!", "bas");
// $db = new MysqliDb("localhost", "root", "root", "bas");
$db->join("bas_sp_orders o", "o.orderId = r.orderId", "LEFT");
$db->orderBy("r.updatedDate");
$data = $db->get("bas_prototype_review r", null, "r.*, o.orderNumberFormated");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prototype Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>

<body>
    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Name</th>
                <th scope="col">Order Id</th>
                <th scope="col">sku</th>
                <th scope="col">Design</th>
                <th scope="col">Value For Money</th>
                <th scope="col">Strap</th>
                <th scope="col">Buckle</th>
                <th scope="col">Durability</th>
                <th scope="col">Functionality</th>
                <th scope="col">Customer Support</th>
                <th scope="col">Comfort</th>
                <th scope="col">Average</th>
                <th scope="col">Overall Review</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $count = 0;
            foreach ($data as $row) {
            ?>
                <tr>
                    <td><?= ++$count ?></td>
                    <td><?= $row["firstname"] . " " . $row["lastname"] ?></td>
                    <td><?= $row["orderNumberFormated"] ?></td>
                    <td><?= $row["sku"] ?></td>
                    <td><?= $row["design"] ?></td>
                    <td><?= $row["valueForMoney"] ?></td>
                    <td><?= $row["strap"] ?></td>
                    <td><?= $row["buckle"] ?></td>
                    <td><?= $row["durability"] ?></td>
                    <td><?= $row["functionality"] ?></td>
                    <td><?= $row["support"] ?></td>
                    <td><?= $row["comfort"] ?></td>
                    <td>
                        <?= (($row["design"] + $row["valueForMoney"] + $row["strap"] + $row["buckle"] + $row["durability"] + $row["functionality"] + $row["support"] + $row["comfort"]) / 8) ?>
                    </td>
                    <td><?= $row["review"] ?></td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
    </script>
</body>

</html>