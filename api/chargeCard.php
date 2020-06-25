<?php
require '../vendor/autoload.php';
require_once '../constants/PaymentCodeConstants.php';

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

define("AUTHORIZENET_LOG_FILE", "phplog");

function chargeCreditCard($email, $card, $charge, $cvc, $expiration, $reason)
{
    //* MerchantAuthentication Info (KEY IN OBJ)
    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName(\PaymentCodeConstants::MERCHANT_LOGIN_ID);
    $merchantAuthentication->setTransactionKey(\PaymentCodeConstants::MERCHANT_TRANSACTION_KEY);

    //* Transaction Reference Id (KEY IN OBJ)
    $refId = "ref" . time();

    // Create The Payment Data
    $creditCard = new AnetAPI\CreditCardType();
    $creditCard->setCardNumber($card);
    $creditCard->setExpirationDate($expiration);
    $creditCard->setCardCode($cvc);

    // Add The Payment Data PaymentType Object
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setCreditCard($creditCard);

    // Create Order Info
    $order = new AnetAPI\OrderType();
    $order->setInvoiceNumber("10101"); //! Would Make Dynamic In Full Imp
    $order->setDescription($reason);

    // NOT MAKING DYNAMIC FOR CURRENT IMP, AS I DO NOT COLLECT THIS DATA
    $customerAddress = new AnetAPI\CustomerAddressType();
    $customerAddress->setFirstName("Ellen"); //! Would Make Dynamic In Full Imp
    $customerAddress->setLastName("Johnson");
    $customerAddress->setCompany("Souveniropolis");
    $customerAddress->setAddress("14 Main Street");
    $customerAddress->setCity("Pecan Springs");
    $customerAddress->setState("TX");
    $customerAddress->setZip("44628");
    $customerAddress->setCountry("USA");

    // Set Customer's Identifying Information
    $customerData = new AnetAPI\CustomerDataType();
    $customerData->setType("individual");
    $customerData->setId($email); // Use Email As Id
    $customerData->setEmail($email);

    // Transaction Settings
    $duplicateWindowSetting = new AnetAPI\SettingType();
    $duplicateWindowSetting->setSettingName("duplicateWindow");
    $duplicateWindowSetting->setSettingValue("60");

    // Merchant Defined Fields That Come In Response
    $merchantDefinedField1 = new AnetAPI\UserFieldType();
    $merchantDefinedField1->setName("customerLoyaltyNum");
    $merchantDefinedField1->setValue("1128836273");

    $merchantDefinedField2 = new AnetAPI\UserFieldType();
    $merchantDefinedField2->setName("favoriteColor");
    $merchantDefinedField2->setValue("blue");

    //* Transaction Request Data (KEY IN OBJ) => Before Items Added Beneath
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType("authCaptureTransaction");
    $transactionRequestType->setAmount($charge);
    $transactionRequestType->setOrder($order);
    $transactionRequestType->setPayment($paymentOne);
    $transactionRequestType->setBillTo($customerAddress);
    $transactionRequestType->setCustomer($customerData);
    $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
    $transactionRequestType->addToUserFields($merchantDefinedField1);
    $transactionRequestType->addToUserFields($merchantDefinedField2);

    // Assemble The Total Request
    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId($refId);
    $request->setTransactionRequest($transactionRequestType);

    // Create Controller And Get The Response
    $controller = new AnetController\CreateTransactionController($request);
    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if ($response != null) {
        // Check to see if the API request was successfully received and acted upon
        if ($response->getMessages()->getResultCode() == "Ok") {
            // Since the API request was successful, look for a transaction response
            // and parse it to display the results of authorizing the card
            $tresponse = $response->getTransactionResponse();

            if ($tresponse != null && $tresponse->getMessages() != null) {
                $responseObj = array(
                    "type" => "success",
                    "transactionId" => $tresponse->getTransId(),
                    "responseCode" => $tresponse->getResponseCode(),
                    "messageCode" => $tresponse->getMessages()[0]->getCode(),
                    "authCode" => $tresponse->getAuthCode(),
                    "description" => $tresponse->getMessages()[0]->getDescription()
                );

                return $responseObj;
            } else {
                $responseObj = array(
                    "type" => "failed",
                );

                if ($tresponse->getErrors() != null) {
                    $responseObj["errorCode"] = $tresponse->getErrors()[0]->getErrorCode();
                    $responseObj["errorMessage"] = $tresponse->getErrors()[0]->getErrorText();
                }
                return $responseObj;
            }
            // Or, print errors if the API request wasn't successful
        } else {
            $tresponse = $response->getTransactionResponse();
            $responseObj = array("type" => "failed");
            if ($tresponse != null && $tresponse->getErrors() != null) {
                $responseObj["errorCode"] = $tresponse->getErrors()[0]->getErrorCode();
                $responseObj["errorMessage"] = $tresponse->getErrors()[0]->getErrorText();
            } else {
                $responseObj["errorCode"] = $response->getMessages()->getMessage()[0]->getCode();
                $responseObj["errorMessage"] = $response->getMessages()->getMessage()[0]->getText();
            }

            return $responseObj;
        }
    } else {
        $responseObj = array("message" => "No response returned!");
        return $responseObj;
    }

    // return $response;
}
