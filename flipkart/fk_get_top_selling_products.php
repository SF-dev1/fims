<!DOCTYPE html>
<html>

<head>
	<title>Flipkart - Top Selling - <?php echo date('d-M-Y, h:i:s', time()); ?></title>
</head>

<body>
	<?php
	// error_reporting(E_ALL);
	// ini_set('display_errors', '1');
	// echo '<pre>';
	$i = 0;
	$no_of_pages_to_lookup = 50;
	// echo '<pre>';
	for ($page = 1; $page < $no_of_pages_to_lookup + 1; $page++) {

		$url = 'https://2.rome.api.flipkart.com/api/4/page/fetch';
		// $payload = '{"pageUri":"/search?q=mens+watches&as=on&as-show=on&as-pos=0&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"1m4iimob7k0000001552547772809","sqid":"vnj1ylvugw0000001552547780832"}}'; // MENS WRIST WATCH
		// $payload = '{"pageUri":"/search?q=skmei&as=on&as-show=on&otracker=AS_QueryStore_OrganicAutoSuggest_0_5&otracker1=AS_QueryStore_OrganicAutoSuggest_0_5&as-pos=0&as-type=RECENT&as-backfill=on&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"js0b7n9yv40000001552553162395","sqid":"usc8bz4vio0000001552553177869"}}';
		// $payload = '{"pageUri":"/search?q=women+watches&as=on&as-show=on&otracker=AS_Query_HistoryAutoSuggest_0_7&otracker1=AS_Query_HistoryAutoSuggest_0_7&as-pos=0&as-type=HISTORY&as-backfill=on&sort=popularity","pageContext":{"paginatedFetch":true,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"1m4iimob7k0000001552547772809","sqid":"vnj1ylvugw0000001552547780832"}}';
		// $payload = '{"pageUri":"/watches/wrist-watches/pr?sid=r18,f13","pageContext":{"paginatedFetch":false,"pageNumber":'.$page.',"fetchSeoData":true},"requestContext":{"type":"BROWSE_PAGE","ssid":"hgu5mn6hnk0000001553852030359","sqid":"9vsmbqcrxs0000001553852030359"}}'; // All Wrist Watch
		// $payload = '{"pageUri":"/search?q=wrist+watch&sid=r18%2Cf13&as=on&as-show=on&otracker=AS_QueryStore_OrganicAutoSuggest_1_5_na_na_na&otracker1=AS_QueryStore_OrganicAutoSuggest_1_5_na_na_na&as-pos=1&as-type=RECENT&suggestionId=wrist+watch%7CWrist+Watches&requestId=2fe41215-30da-4f55-a8d7-9274b4808db6&as-backfill=on&sort=popularity","pageContext":{"fetchSeoData":true,"paginatedFetch":true,"pageNumber":'.$page.'},"requestContext":{"type":"BROWSE_PAGE","ssid":"a8eep211tc0000001601994881132","sqid":"d1ujkyeeds0000001601994895326"}}';
		$payload = '{"pageUri":"/search?q=wrist+watch&as=on&as-show=on&otracker=AS_Query_OrganicAutoSuggest_4_11_na_na_na&otracker1=AS_Query_OrganicAutoSuggest_4_11_na_na_na&as-pos=4&as-type=RECENT&suggestionId=wrist+watch&requestId=59fd1271-e65a-48c9-aa0e-046c7e7a4a70&as-backfill=on","pageContext":{"fetchSeoData":false,"paginatedFetch":false,"pageNumber":' . $page . '},"requestContext":{"type":"BROWSE_PAGE","ssid":"rcy7o5b1ls0000001600516886822","sqid":"o5543tyfkg0000001600516886822"}}';

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
				"Content-Length: " . strlen($payload),
				"Host: 2.rome.api.flipkart.com",
				"X-User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36 FKUA/website/42/website/Desktop",
				"User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.102 Safari/537.36",
				"Content-Type: application/json",
				"Origin: https://www.flipkart.com",
				"Referer: https://www.flipkart.com/",
				"Cookie: SN=VI0EC5ADFB74E7450EA46977C49D1A6026.TOKCC49889D3B5F49AEB3D1B84D57B40A6F." . time() . ".LI"
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

						$j = 1;
						$total_ratings = 0;
						foreach ($product->rating->breakup as $rating) {
							$total_ratings += $rating;
							$j++;
						}

						$fsn = $product->id;
						$data[$i]['id'] = $product->listingId;
						$data[$i]['brand'] = $product->productBrand;
						$data[$i]['title'] = $product->titles->title;
						$data[$i]['rating'] = $product->rating->average . ' (' . $total_ratings . ')';
						$data[$i]['final_price'] = $product->pricing->finalPrice->value;
						$data[$i]['is_ad'] = isset($products->adInfo);
						$i++;
					}
				}
			}
		}
	}
	$output = "<style>table {font-family: arial, sans-serif;border-collapse: collapse;font-size:12px;margin:0 auto;}td, th {border: 1px solid #000;text-align: left;padding: 3px;}th {text-align:center;}tr:nth-child(even) {background-color: #dddddd;}</style>";
	$output .= "<table><thead><th>Rank</th><th></th><th>LID</th><th>Brand</th><th>Title</th><th>Avg. Rating</th><th>Price</th></thead><tbody>";
	$rank = 1;
	foreach ($data as $products) {
		$is_ads = ($products['is_ad'] ? "Ads" : "");
		$product_title = strlen($products["title"]) > 100 ? substr($products["title"], 0, 97) . "..." : $products["title"];
		$output .= '<tr>';
		$output .= '<td>' . $rank . '</td>';
		$output .= '<td><a href="https://dl.flipkart.com/dl/product/p/itme?pid=' . substr($products["id"], 3, 16) . '" target="_blank">View</a></td>';
		$output .= '<td>' . $products["id"] . '</td>';
		$output .= '<td>' . $products["brand"] . '</td>';
		$output .= '<td>' . $product_title . '</td>';
		$output .= '<td>' . $products["rating"] . '</td>';
		$output .= '<td>' . $products["final_price"] . '</td>';
		$output .= '</tr>';
		$rank++;
	}
	$output .= "</tbody></table>";
	echo $output;
	?>
</body>

</html>