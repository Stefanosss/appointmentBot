<?php
// webhook.php
//
// Dialogflow calls this service using POST request.
// Place this file in the document root of your server. Your server must be accessible at the below URL:
// http://<hostname>/webhook.php
//
// This service just extracts the intent and parameters (entities) from the user supplied text 
// and returns to Dialogflow.
//
$method = $_SERVER['REQUEST_METHOD'];
//
// Process only when request method is POST
//



require __DIR__ . '/vendor/autoload.php';




if($method == 'POST'){
        $requestBody = file_get_contents('php://input');
        $json = json_decode($requestBody);
        $text = $json->result->resolvedQuery;
        $date = (!empty($json->result->parameters->date)) ? $json->result->parameters->date : '';
        $time  = (!empty($json->result->parameters->time)) ? $json->result->parameters->time : '';
        $any  = (!empty($json->result->parameters->any)) ? $json->result->parameters->any : '';
        $reason  = (!empty($json->result->parameters->reason)) ? $json->result->parameters->reason : '';
        $intent   = (!empty($json->result->metadata->intentName)) ? $json->result->metadata->intentName : '';
    
        
        
        $responseText = prepareResponse($intent, $text, $date, $any, $time, $reason);
        $response = new \stdClass();
        $response->speech = $responseText;
        $response->displayText = $responseText;
        $response->source = "webhook";
        echo json_encode($response);
}
else
{
        echo "Method not allowed";
}
function prepareResponse($intent, $text, $date, $any, $time, $reason)
    {
    
    /*$tijd = $time;
    $dt = new DateTime($tijd);
    $dt->add(new DateInterval('PT1H'));*/
    
    //date_add($time, date_interval_create_from_date_string('1 hour'));
    
    $s = $time;
    
    $form = date_create_from_format('H:i:s', $s);
    
    $form->add(new DateInterval('PT1H'));
    
    $result = $form->format('H:i:s');

    
    /**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Calendar API PHP Quickstart');
    $client->setScopes(Google_Service_Calendar::CALENDAR);
    $client->setAuthConfig('credentials.json');
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = 'token.json';
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);

    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Calendar($client);

$event = new Google_Service_Calendar_Event(array(
  'summary' => $reason,
  'location' => '',
  'description' => 'Last name of the patient: ' . $any,
  'start' => array(
    'dateTime' => $date . 'T' . $time . '+02:00',
    'timeZone' => 'Europe/Brussels',
  ),
  'end' => array(
    'dateTime' => $date . 'T' . $result . '+02:00',
    'timeZone' => 'Europe/Brussels',
  ),
  'recurrence' => array(
    'RRULE:FREQ=DAILY;COUNT=1'
  ),
  'reminders' => array(
    'useDefault' => FALSE,
    'overrides' => array(
      array('method' => 'email', 'minutes' => 24 * 60),
      array('method' => 'popup', 'minutes' => 10),
    ),
  ),
));

$calendarId = 'primary';
$event = $service->events->insert($calendarId, $event);
//printf('Event created: %s\n', $event->htmlLink);


    
    return "Appointment correctly made, see you soon!";
    
}



?>


