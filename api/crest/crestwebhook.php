<?php

// ------------------------------------------------------------------------------------------------
// uncomment the following line if you are getting 500 error messages
// 
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// echo '<pre>';
// ------------------------------------------------------------------------------------------------
include_once(ROOT_PATH . '/config.php');

class CrestWebhook
{

    public $refreshToken;
    public $accessToken;
    const baseURL = "https://app.getcrest.ai/api";
    const FILE_PATH = ROOT_PATH . "/uploads/crest/";
    const SALES = "sales_master";
    const PRODUCT = "product_master";
    const STOCK = "inventory_master";
    private $salesKeys = array("SKU", "channel", "date", "Quantity");
    private $productKeys = array("SKU", "SKU Name", "SKU Description", "UOM", "Price", "Min Order Quantity", "item_group", "Batch Size", "shelf_life_in_days", "Max Inventory Cover", "Min Inventory Cover");
    private $stockKeys = array("SKU", "Date", "Stock", "Warehouse Name", "Warehouse id");

    function __construct()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::baseURL . '/ms_iam/token/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "email": "stylefeathers@gmail.com",
                "password": "Crest@1234"
            }',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'Content-Type: application/json',
                'Cookie: prod_session_key=zi1ldnvk4e6zg8ldwxywino5eht0k0ju'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        if (isset($response['message']) && $response['message'] == "FAILURE" && $response["result"] == "Another session is currently active") {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::baseURL . '/ms_iam/user/session/override/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                "email": "stylefeathers@gmail.com",
                "password": "Crest@1234"
            }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json',
                    'Cookie: prod_session_key=zi1ldnvk4e6zg8ldwxywino5eht0k0ju'
                ),
            ));

            curl_setopt_array($curl, array(
                CURLOPT_URL => self::baseURL . '/ms_iam/token/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                    "email": "stylefeathers@gmail.com",
                    "password": "Crest@1234"
                }',
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'Content-Type: application/json',
                    'Cookie: prod_session_key=zi1ldnvk4e6zg8ldwxywino5eht0k0ju'
                ),
            ));
            $responseUpdate = curl_exec($curl);
        }
        if (isset($responseUpdate)) {
            $response = json_decode($responseUpdate, true);
        }
        $this->accessToken = $response['access'];
        $this->refreshToken = $response['refresh'];
    }

    function refreshToken()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::baseURL . '/ms_iam/token/refresh/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "refresh": "' . $this->refreshToken . '"
            }',
            CURLOPT_HTTPHEADER => array(
                'accept: application/json',
                'Content-Type: application/json',
                'Cookie: prod_session_key=2s6fq70exmlqz6q7vljz7zmzbdqcz0mc'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $this->accessToken = $response['access'];
        $this->refreshToken = $response['refresh'];
    }

    function sendRequest($url, $verb, $formData)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::baseURL . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($verb),
            CURLOPT_POSTFIELDS => $formData,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $this->accessToken
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        $res = json_decode($res->result);
        if (isset($res->code) && $res->code == "token_not_valid") {
            $this->refreshToken();
            $response = $this->sendRequest($url, $verb, $formData);
        }
        return $response;
    }

    function salesMaster()
    {
        $formData = array(
            "key_columns_name" => "SKU",
            "timestamp_column_name" => "",
            "date_format" => "YYYY-mm-dd",
            "target_column_name" => "",
            "timestamp_frequency" => "",
            "product_hierarchy" => "",
            "supply_chain_hierarchy" => "",
            "split_cols" => "",
            "cols_aggregation" => array(
                "sku" => "",
                "date" => "date",
                "qty" => "Quantity",
                "platform" => "channel",
                "country" => "",
            ),
        );

        $response = $this->sendRequest('/ms_data_preparation/master_data/metadata/sales_master/', 'POST', json_encode($formData));
        return json_decode($response, true);
    }

    function inventoryMaster()
    {
        $formData = array(
            "product_column_name" => "SKU",
            "date_column_name" => "Date",
            "quantity_column_name" => "Stock",
            "warehouse_id_column_name" => "Warehouse id",
            "warehouse_name_column_name" => "Warehouse Name"
        );
        $response = $this->sendRequest('/ms_data_preparation/master_data/metadata/inventory_master/', 'POST', json_encode($formData));
        return json_decode($response, true);
    }

    function productMaster()
    {
        $formData = array(
            "product_id_column" => "SKU",
            "name_column" => "SKU Name",
            "description_column" => "SKU Description",
            "uom_column_name" => "UOM",
            "moq_column_name" => "Min Order Quantity",
            "batch_size_column_name" => "Batch Size",
            "volume_column" => "",
            "weight_column" => "",
            "hierarchy1_column" => "",
            "hierarchy2_column" => "",
            "hierarchy3_column" => "",
            "cost_price_column" => "",
            "selling_price_column" => "Price",
            "production_lead_time_column" => "",
            "internal_shelf_life_column" => "",
            "shelf_life_column" => "shelf_life_in_days",
            "discount_column" => "",
            "promotion_spend_column" => "",
            "effective_start_date_column" => "",
        );

        $response = $this->sendRequest('/ms_data_preparation/master_data/metadata/product_master/', 'POST', json_encode($formData));
        return json_decode($response, true);
    }

    function createCSV($type)
    {
        switch ($type) {
            case self::SALES:
                $fileName = self::FILE_PATH . "sales_master_" . date("Y_m_d") . ".csv";
                $data = $this->salesKeys;
                break;
            case self::PRODUCT:
                $fileName = self::FILE_PATH . "product_master_" . date("Y_m_d") . ".csv";
                $data = $this->productKeys;
                break;
            case self::STOCK:
                $fileName = self::FILE_PATH . "inventory_master_" . date("Y_m_d") . ".csv";
                $data = $this->stockKeys;
                break;
        }
        $csvFile = fopen($fileName, 'w');
        fputcsv($csvFile, $data);
        fclose($csvFile);
        return $fileName;
    }

    function generateCSV($data, $type)
    {
        switch ($type) {
            case self::SALES:
                $fileName = self::FILE_PATH . "sales_master_" . date("Y_m_d") . ".csv";
                $dataKeys = $this->salesKeys;
                break;
            case self::PRODUCT:
                $fileName = self::FILE_PATH . "product_master_" . date("Y_m_d") . ".csv";
                $dataKeys = $this->productKeys;
                break;
            case self::STOCK:
                $fileName = self::FILE_PATH . "inventory_master_" . date("Y_m_d") . ".csv";
                $dataKeys = $this->stockKeys;
                break;
        }

        if (!file_exists($fileName))
            $fileName = $this->createCSV($type);

        $csvFile = fopen($fileName, 'a');
        foreach ($data as $row) {
            $row_i = array();
            foreach ($dataKeys as $key) {
                $row_i[] = $row[$key];
            }
            fputcsv($csvFile, $row_i);
        }
        fclose($csvFile);
        return $fileName;
    }

    function uploadData()
    {
        global $log;

        if (file_exists(self::FILE_PATH . "sales_master_" . date("Y_m_d") . ".csv")) {
            $response = $this->sendRequest('/ms_data_preparation/master_data/' . self::SALES . '/data/', 'POST', array('file' => new CURLFILE(self::FILE_PATH . "sales_master_" . date("Y_m_d") . ".csv")));
            $log->write(date("d M, Y H:i:s") . ":\n:: Sales Details Upload Attempted to Crest\n:: File: sales_master_" . date("Y_m_d") . ".csv\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
            $response = $this->salesMaster();
            $log->write(date("d M, Y H:i:s") . ":\n:: Sales Details Meta Data Upload Attempted to Crest\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
        }
        if (file_exists(self::FILE_PATH . "product_master_" . date("Y_m_d") . ".csv")) {
            $response = $this->sendRequest('/ms_data_preparation/master_data/' . self::PRODUCT . '/data/', 'POST', array('file' => new CURLFILE(self::FILE_PATH . "product_master_" . date("Y_m_d") . ".csv")));
            $log->write(date("d M, Y H:i:s") . ":\n:: Product Details Upload Attempted to Crest\n:: File: product_master_" . date("Y_m_d") . ".csv\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
            $response = $this->productMaster();
            $log->write(date("d M, Y H:i:s") . ":\n:: Product Details Meta Data Upload Attempted to Crest\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
        }
        if (file_exists(self::FILE_PATH . "inventory_master_" . date("Y_m_d") . ".csv")) {
            $response = $this->sendRequest('/ms_data_preparation/master_data/' . self::STOCK . '/data/', 'POST', array('file' => new CURLFILE(self::FILE_PATH . "inventory_master_" . date("Y_m_d") . ".csv")));
            $log->write(date("d M, Y H:i:s") . ":\n:: Inventory Details Upload Attempted to Crest\n:: File: inventory_master_" . date("Y_m_d") . ".csv\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
            $response = $this->inventoryMaster();
            $log->write(date("d M, Y H:i:s") . ":\n:: Inventory Details Meta Data Upload Attempted to Crest\n:: Response: " . ($response), "crest/" . date("Y_m_d"));
        }
    }
}
