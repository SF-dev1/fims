<?php

class whatsappBik
{

    private $auth_key;
    private $secret_key;
    private $app_id;
    private $auth_token;
    private $api_endpoint;

    function __construct($client)
    {
        $this->auth_key = $client->whatsappClientKey;
        $this->secret_key = $client->whatsappClientSecret;
        $this->app_id = $client->whatsappClientAppId;
        $this->auth_token = $client->whatsappClientAuthToken;
        $this->api_endpoint = $client->whatsappClientEndpoint;
    }

    private function sendRequest($url, $postFields)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_endpoint . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postFields, true),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . $this->auth_token
            ),
        ));

        $response = curl_exec($curl);
        // cvd(json_encode($postFields, true));

        curl_close($curl);
        return $response;
    }

    public function sendMessage($data)
    {
        $postFields = array(
            "appId" => $this->app_id,
            "medium" => "whatsapp"
        );

        if ($data["templateName"] == "request_address") {
            $url = "bikPlatformFunctions-initiateFlow";
            $postFields["phoneNumber"] = "+91" . $data["contactNumber"];
            $postFields["flowId"] = $data["templateId"];
            $postFields["carryPayload"]["customerName"] = $data["templateData"]["body"][0]; // static variable customerName 
            $postFields["carryPayload"]["orderNumber"] = $data["templateData"]["body"][1]; // static varible orderNumber
        } else {
            $url = "bikPlatformFunctions-sendTemplateMessage";
            $postFields["contactIdentifier"] = "+91" . $data["contactNumber"];
            $postFields["payload"]["templateId"] = $data["templateId"];
            $postFields["payload"]["components"]["header"] = $data["templateData"]["header"];
            $postFields["payload"]["components"]["body"] = $data["templateData"]["body"];
            $postFields["payload"]["components"]["button"] = $data["templateData"]["button"];
        }
        return json_decode($this->sendRequest($url, $postFields), true);
    }
}
