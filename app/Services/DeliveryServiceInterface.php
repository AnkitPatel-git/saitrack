<?php

namespace App\Services;

interface DeliveryServiceInterface
{
    /**
     * Create a waybill for shipment
     *
     * @param array $payload
     * @param mixed $bookingId
     * @return array
     */
    public function createWaybill(array $payload, $bookingId = null): array;

    /**
     * Track a shipment
     *
     * @param string $awbNumber
     * @param mixed $bookingId
     * @return array
     */
    public function trackShipment(string $awbNumber, $bookingId = null): array;

    /**
     * Get the provider name
     *
     * @return string
     */
    public function getProviderName(): string;

    /**
     * Cancel a waybill
     *
     * @param string $waybillNumber Waybill/LR number to cancel
     * @param mixed $bookingId Optional booking ID
     * @param int $test Test mode (1 for test, 0 for production)
     * @return array
     */
    public function cancelWaybill(string $waybillNumber, $bookingId = null, int $test = 1): array;
}
