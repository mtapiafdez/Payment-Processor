<?php


/*
* MAIN API CODE
* Sources
- https://stackoverflow.com/questions/2385701/regular-expression-for-first-and-last-name
- https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
- https://stackoverflow.com/questions/12026842/how-to-validate-an-email-address-in-php
- https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php
- https://www.geeksforgeeks.org/how-to-encrypt-and-decrypt-a-php-string/
- https://dba.stackexchange.com/questions/148308/how-to-remove-6-months-old-data-automatically-from-mysql-innodb-table
- https://developer.authorize.net/api/reference/index.html
*/

// Setup Post Object For Value Extraction
$json = file_get_contents("php://input");
$postObj = json_decode($json);

$type = $_GET["type"];

// Manage API Call Differently Based On Query Parameter
switch ($type) {
    case "send":
        $firstName = $postObj->firstName;
        $lastName = $postObj->lastName;
        $email = $postObj->email;
        $charge = (float) $postObj->charge;
        $card = $postObj->card;
        $expiration = $postObj->expiration;
        $cvc = (int) $postObj->cvc;
        $reason = !empty($postObj->reason) ? $postObj->reason : null;

        $message = validateValues($firstName, $lastName, $email, $charge, $card, $expiration, $cvc, $reason);

        if ($message === "VALID") {
            require "./chargeCard.php";

            $chargePerson = false; // If Want To Remove Charges From Flow
            $expParsed = substr($expiration, 0, 7);
            $chargeRes = $chargePerson ? chargeCreditCard($email, $card, $charge, $cvc, $expParsed, $reason) : 'SKIP';


            if ($chargeRes === "SKIP" || $chargeRes["type"] === "success") {
                $dbAddSuccess = addToDatabase($firstName, $lastName, $email, $charge, $card, $expiration, $cvc, $reason);
                if ($dbAddSuccess === true) {
                    $response = array("type" => "SUCCESS", "message" => "Transaction Successful!");
                    header('Content-type: application/json');
                    echo json_encode($response);
                } else {
                    $errResponse = array("type" => "FAIL", "message" => "DB Add Failed");
                    header('Content-type: application/json');
                    echo json_encode($errResponse);
                }
            } else {
                $errResponse = array("type" => "FAIL", "message" => "We could not charge your card!");
                header('Content-type: application/json');
                echo json_encode($errResponse);
            }
        } else {
            $response = array("type" => "FAIL", "message" => $message);
            header('Content-type: application/json');
            echo json_encode($response);
        }

        break;

    case "receive":
        $payment_id = $postObj->paymentId;

        $data = getPaymentFromDatabase($payment_id);

        if ($data["message"] === "SUCCESS") {
            $response = array("type" => "SUCCESS", "message" => "Transaction Successful!", "data" => $data);
            header('Content-type: application/json');
            echo json_encode($response);
        } else {
            $response = array("type" => "FAIL", "message" => "There was an issue!");
            header('Content-type: application/json');
            echo json_encode($response);
        }

        break;

    case "refresh":
        require "../includes/dbConnect.php";

        $query = "SELECT payment_id, first_name, last_name FROM payment_processor;";
        $result = $conn->query($query);
        $results = array();

        while ($record = $result->fetch_assoc()) {
            $results[] = $record;
        }

        header('Content-type: application/json');
        echo json_encode($results);

        require "../includes/dbDisconnect.php";

        break;

    case "test-charge":
        // Testing Purposes So Can't Get Here If Boolean Is False
        $testing = false;

        if ($testing) {
            require "./chargeCard.php";

            $email = $postObj->email;
            $charge = (float) $postObj->charge;
            $card = $postObj->card;
            $expiration = substr($postObj->expiration, 0, 7);
            $cvc = (int) $postObj->cvc;
            $reason = !empty($postObj->reason) ? $postObj->reason : null;

            $chargeRes = chargeCreditCard($email, $card, $charge, $cvc, $expiration, $reason);

            header('Content-type: application/json');
            echo json_encode($chargeRes);
        } else {
            $response = array("message" => "NOTHING HERE!");
            header('Content-type: application/json');
            echo json_encode($response);
        }

        break;

    default:
        $response = "You should not be here!";
        echo json_encode($response);
}

// Validates Inputs Server Side
function validateValues($firstName, $lastName, $email, $charge, $card, $expiration, $cvc, $reason)
{
    $isFirstName = preg_match("/^[a-z ,.'-]+$/i", $firstName) && strlen($firstName) <= 50;
    $isLastName = preg_match("/^[a-z ,.'-]+$/i", $lastName) && strlen($lastName) <= 50;
    $isEmail = filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 100;
    $isCharge = is_float($charge);
    $isCard = ctype_digit($card) && (strlen($card) >= 13 && strlen($card) <= 19);
    $isCvc = is_numeric($cvc);

    $tempDate = explode("-", $expiration);
    $isExpirationDate = checkdate($tempDate[1], $tempDate[2], $tempDate[0]);

    $isReason = ($reason == null || strlen($reason) <= 200);

    if ($isFirstName && $isLastName && $isEmail && $isCharge && $isCard && $isCvc && $isExpirationDate && $isReason) {
        return "VALID";
    } else {
        return "There is an invalid value!";
    }
}

// Adds Values To Database
function addToDatabase($firstName, $lastName, $email, $charge, $card, $expiration, $cvc, $reason)
{
    require "../includes/dbConnect.php";

    $query = "INSERT INTO payment_processor (first_name, last_name, email, charge, card, expiration_date, cvc, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";

    if (!($stmt = $conn->prepare($query))) {
        require "../includes/dbDisconnect.php";
        return  "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }

    if (!$stmt->bind_param("sssdssis", $pFirstName, $pLastName, $pEmail, $pCharge, $pCard, $pExpDate, $pCvc, $pReason)) {
        require "../includes/dbDisconnect.php";
        return "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $pFirstName = $firstName;
    $pLastName = $lastName;
    $pEmail = $email;
    $pCharge = $charge;
    $pCard = encryptCard($card, $key);
    $pExpDate = $expiration;
    $pCvc = $cvc;
    $pReason = $reason;

    if (!$stmt->execute()) {
        require "../includes/dbDisconnect.php";
        return "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $stmt->close();

    require "../includes/dbDisconnect.php";

    return true;
}

// Gets Details For Payment
function getPaymentFromDatabase($payment_id)
{
    require "../includes/dbConnect.php";

    $query = "SELECT * FROM payment_processor WHERE payment_id = ?;";

    if (!($stmt = $conn->prepare($query))) {
        require "../includes/dbDisconnect.php";
        return  "Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error;
    }

    if (!$stmt->bind_param("i", $paramId)) {
        require "../includes/dbDisconnect.php";
        return "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $paramId = (int) $payment_id;

    if (!$stmt->execute()) {
        require "../includes/dbDisconnect.php";
        return "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $result = $stmt->get_result();
    $record = $result->fetch_assoc();

    $record["decryptedCard"] = decryptCard($record["card"], $key);

    $stmt->close();

    require "../includes/dbDisconnect.php";

    return array("message" => "SUCCESS", "data" => $record);
}

// Encrypt Card
function encryptCard($card, $key)
{
    // Cipher Method
    $cipherMethod = "AES-128-CTR";

    // OpenSSl Encryption Method 
    $ivLength = openssl_cipher_iv_length($cipherMethod);
    $options = 0;

    // Non-NULL Initialization Vector For Encryption 
    $encryptionIV = '1234567891011121';

    // Store Encryption Key 
    $encryptionKey = $key;

    // Encrypt
    $encryptedCard = openssl_encrypt(
        $card,
        $cipherMethod,
        $encryptionKey,
        $options,
        $encryptionIV
    );

    return $encryptedCard;
}

// Decrypt Card
function decryptCard($cardEncrypted, $key)
{
    // Cipher Method
    $cipherMethod = "AES-128-CTR";

    // Options
    $options = 0;

    // Non-NULL Initialization Vector For Decryption 
    $decryptionIV = '1234567891011121';

    // Store Decryption Key
    $decryptionKey = $key;

    // Decrypt
    $decryptedCard = openssl_decrypt(
        $cardEncrypted,
        $cipherMethod,
        $decryptionKey,
        $options,
        $decryptionIV
    );

    return $decryptedCard;
}
