<?php
namespace Razorpay\Api;
use Requests;
/**
 * PayoutLink entity gets used for creating Payout Links for refunds.
 * Few of the methods are only meaningful for PayoutLink system and calling those
 * for against/for a PayoutLink would throw Bad request error.
 */
class PayoutLink extends Entity
{   
    /**
     * Creates PayoutLinks for refund.
     *
     * @param array $attributes
     *
     * @return PayoutLinks
     */
    public function create($attributes = array())
    {
        $attributes = json_encode($attributes);
        Request::addHeader('Content-Type', 'application/json');
        $entityUrl = str_replace('_', '-', $this->getEntityUrl());
        return $this->request('POST', $entityUrl, $attributes);
    }
    /**
     * Fetches payout link entity with given id
     *
     * @param string $id
     *
     * @return PayoutLinks
     */
    public function fetch($id)
    {
        $relativeUrl = str_replace('_', '-', $this->getEntityUrl());
        $entityUrl = $relativeUrl . $id;
        return $this->request('GET', $entityUrl);
    }
    /**
     * Fetches multiple PayoutLinks with given query options
     *
     * @param array $options
     *
     * @return PayoutLinks
     */
    public function all($options = array())
    {
        
        $entityUrl = str_replace('_', '-', $this->getEntityUrl());
        
        return $this->request('GET', $entityUrl, $options);
    }
    /**
     * Cancels issued payout link
     *
     * @return PayoutLinks
     */
    public function cancel()
    {
        $relativeUrl = str_replace('_', '-', $this->getEntityUrl());
        
        $entityUrl = $relativeUrl . $this->id . '/cancel';
        return $this->request(Requests::POST, $entityUrl);
    }
}