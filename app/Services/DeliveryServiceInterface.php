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
}
