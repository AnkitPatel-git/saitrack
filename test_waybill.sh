#!/bin/bash

# Test script for waybill creation API
# Make sure to set the correct token and service_provider in the webhook_orgs table

# Generate unique codes for each test run
TIMESTAMP=$(date +%s)
SHIPMENT_CODE="NONB$(printf "%06d" $((RANDOM % 1000000)))"
ORDER_CODE="SO$(printf "%06d" $((RANDOM % 1000000)))"
sleep 1
SALE_ORDER_CODE="SO$(printf "%06d" $((RANDOM % 1000000)))"

echo "Testing with Shipment Code: $SHIPMENT_CODE, Order Code: $ORDER_CODE"
echo "Service Provider should be: bluedart"
echo ""

curl --location 'http://localhost:8000/api/unicommerce/waybill' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer d060d0212549b8cfe730c206f4b545a689cfaf766093da2a119ced93df95aaf3' \
--data-raw '{
  "serviceType": "Roadways",
  "handOverMode": "Counter Drop",
  "returnShipmentFlag": "false",
  "Shipment": {
    "shipmentTag": "",
    "code": "'"$SHIPMENT_CODE"'",
    "customField": [
        {"name":"invoice_link",
        "value":"5678903424"}
    ],
    "SaleOrderCode": "'"$SALE_ORDER_CODE"'",
    "orderCode": "'"$ORDER_CODE"'",
    "channelCode": "CUSTOM",
    "channelName": "CUSTOM",
    "invoiceCode": "INSakshay0912",
    "orderDate": "19-Nov-2025 15:17:24",
    "fullFilllmentTat": "21-Nov-2025 15:17:50",
    "weight": "27000",
    "length": "1955",
    "height": "92",
    "breadth": "1065",
    "source": "unicommerce",
    "numberOfBoxes": "1",
    "items": [
      {
        "name": "SPGS G-BS Module 365Wp(Mono M10)-100 HC",
        "description": "SPGS G-BS Module 365Wp(Mono M10)-100 HC",
        "quantity": 1,
        "skuCode": "WSMDIB-365",
        "itemPrice": 4599.00,
        "brand": " ",
        "color": "",
        "category": "Mono PERC",
        "size": "",
        "item_details": "",
        "ean": "",
        "imageURL": "WSMDIB-365",
        "hsnCode": "",
        "tags": ""
      }
    ]
  },
  "deliveryAddressId": "",
  "deliveryAddressDetails": {
    "name": "Rahul  Chaudhary ",
    "email": "rahulsirmgcc@gmail.com",
    "phone": "9936242392",
    "address1": "Lohjhar Nahar ",
    "address2": "Bhaisahi Bazar ",
    "district": "",
    "pincode": "274203",
    "city": "Kushinagar ",
    "state": "Uttar Pradesh",
    "country": "India",
    "stateCode": "UP",
    "countryCode": "IN",
    "gstin": "",
    "alternateP hone": ""
  },
  "pickupAddressId": "",
  "pickupAddressDetails": {
    "name": "contact_person",
    "email": "",
    "phone": "9999999889",
    "address1": "Gurgaon",
    "address2": "",
    "pincode": "324005",
    "city": "Gurgaon",
    "state": "Haryana",
    "country": "India",
    "stateCode": "HR",
    "countryCode": "IN",
    "gstin": null,
    "latitude": "",
    "longitude": ""
  },
  "returnAddressId": "",
  "returnAddressDetails": {
    "pincode": "324005",
    "country": "India",
    "address2": "",
    "city": "Gurgaon",
    "address1": "Gurgaon",
    "latitude": "",
    "phone": "9999999889",
    "countryCode": "IN",
    "name": "contact_person",
    "stateCode": "HR",
    "state": "Haryana",
    "email": "",
    "longitude": ""
  },
  "currencyCode": "INR",
  "paymentMode": "PREPAID",
  "totalAmount": "4599.00",
  "collectableAmount": "0",
  "courierName": ""
}'

