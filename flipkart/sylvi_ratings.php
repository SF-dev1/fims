<html>

<head>
	<title>Flipkart - Sylvi -
		<?php echo date('d-M-Y, h:i:s', time()); ?>
	</title>
</head>

<body>
	<?php
	$i = 0;
	$no_of_pages_to_lookup = 10;
	echo '<pre>';
	for ($page = 1; $page < $no_of_pages_to_lookup + 1; $page++) {

		$url = 'https://www.flipkart.com/api/4/page/fetch';
		// $payload = '{"pageUri":"/search?q=mens+watches&as=on&as-show=on&as-pos=0&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"1m4iimob7k0000001552547772809","sqid":"vnj1ylvugw0000001552547780832"}}'; // MENS WRIST WATCH
		// $payload = '{"pageUri":"/search?q=skmei&as=on&as-show=on&otracker=AS_QueryStore_OrganicAutoSuggest_0_5&otracker1=AS_QueryStore_OrganicAutoSuggest_0_5&as-pos=0&as-type=RECENT&as-backfill=on&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"js0b7n9yv40000001552553162395","sqid":"usc8bz4vio0000001552553177869"}}';
		// $payload = '{"pageUri":"/search?q=women+watches&as=on&as-show=on&otracker=AS_Query_HistoryAutoSuggest_0_7&otracker1=AS_Query_HistoryAutoSuggest_0_7&as-pos=0&as-type=HISTORY&as-backfill=on&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"1m4iimob7k0000001552547772809","sqid":"vnj1ylvugw0000001552547780832"}}';
		$payload = '{"pageUri":"/all/sylvi~brand/pr?sid=all","pageContext":{"paginatedFetch":false,"pageNumber":' . $page . ',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"hgu5mn6hnk0000001553852030359","sqid":"9vsmbqcrxs0000001553852030359"}}'; // All Wrist Watch

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => $payload,
			CURLOPT_HTTPHEADER => array(
				"Cache-Control: no-cache",
				"Content-Type: application/json",
				"Content-Length: " . strlen($payload),
				"Pragma: no-cache",
				"X-user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36 FKUA/website/41/website/Desktop",
				"cache-control: no-cache"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			$response = json_decode($response);
			$slots = $response->RESPONSE->slots;
			foreach ($slots as $slot) {
				if ($slot->widget->type == "PRODUCT_SUMMARY") {
					foreach ($slot->widget->data->products as $products) {
						// if (isset($products->adInfo))
						// 	continue;
						// var_dump($products->productInfo->value);
						$product = $products->productInfo->value;

						$fsn = $product->id;
						$data[$i]['id'] = $product->id;
						$data[$i]['brand'] = $product->titles->superTitle;
						$data[$i]['title'] = substr($product->titles->title, 0, 90);
						$data[$i]['final_price'] = $product->pricing->finalPrice->value;
						$data[$i]['reviews'] = $product->rating->reviewCount;
						$data[$i]['rating'] = $product->rating->average;
						$j = 1;
						$total_ratings = 0;
						foreach ($product->rating->breakup as $rating) {
							$data[$i]['rating_' . $j] = $rating;
							$total_ratings += $rating;
							$j++;
						}
						$data[$i]['total_ratings'] = $total_ratings;

						$i++;
					}
				}
			}
		}
	}
	$output = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
	$output .= "<table><thead><th>#</th><th></th><th>FSN</th><th>Brand</th><th>Title</th><th>Current SP</th><th>Reviews</th><th>Avg. Rating</th><th>Total Ratings</th><th>5 star</th><th>4 star</th><th>3 star</th><th>2 star</th><th>1 star</th></thead><tbody>";
	$rank = 1;
	foreach ($data as $products) {
		$output .= '<tr>';
		$output .= '<td>' . $rank . '</td>';
		$output .= '<td><a href="https://dl.flipkart.com/dl/product/p/itme?pid=' . $products["id"] . '" target="_blank">View</a></td>';
		$output .= '<td>' . $products["id"] . '</td>';
		$output .= '<td>' . $products["brand"] . '</td>';
		$output .= '<td>' . $products["title"] . '</td>';
		$output .= '<td>' . $products["final_price"] . '</td>';
		$output .= '<td>' . $products["reviews"] . '</td>';
		$output .= '<td>' . $products["rating"] . '</td>';
		$output .= '<td>' . $products["total_ratings"] . '</td>';
		$output .= '<td>' . $products["rating_5"] . '</td>';
		$output .= '<td>' . $products["rating_4"] . '</td>';
		$output .= '<td>' . $products["rating_3"] . '</td>';
		$output .= '<td>' . $products["rating_2"] . '</td>';
		$output .= '<td>' . $products["rating_1"] . '</td>';
		$output .= '</tr>';
		$rank++;
	}
	$output .= "</tbody></table>";
	echo $output;
	?>
</body>

</html>