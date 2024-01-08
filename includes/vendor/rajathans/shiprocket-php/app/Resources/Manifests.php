<?php 

namespace Shiprocket\Resources;

trait Manifests
{
    public function generateManifests(
        $shipment_ids = []
    ) {
        return $this->request(
            'post', 
            'manifests/generate',
            [
                'shipment_id' => $shipment_ids
            ]
        );
    }

    public function printManifests(
        $order_ids = []
    ) {
        return $this->request(
            'post', 
            'manifests/print',
            [
                'order_ids' => $order_ids
            ]
        );
    }

    /**
     * Makes a request to the Shiprocket API and returns the response.
     *
     * @param    string $verb       The Http verb to use
     * @param    string $path       The path of the APi after the domain
     * @param    array  $parameters Parameters
     *
     * @return   stdClass The JSON response from the request
     * @throws   Exception
     */
    abstract protected function request($verb, $path, $parameters = []);
}