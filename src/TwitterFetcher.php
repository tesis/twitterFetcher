<?php
/**
 * This file is part of the Tesis framework.
 *
 * PHP version 5.6
 *
 * @author     Tereza Simcic <tereza.simcic@gmail.com>
 * @copyright  2014-2015 Tesis, Tereza Simcic
 * @license    MIT
 * @link       https://github.com/tesis/twitterFetcher
 *
 */

namespace Tesis\Socials\Twitter;

/**
 * Class TwitterFetcher
 *
 * PHP version 5.6
 *
 * @package    Photos
 * @author     Tereza Simcic <tereza.simcic@gmail.com>
 *
 * TwitterFetcher fethces images from Twitter
 *
 */

class TwitterFetcher
{
    /**
     * @access private
     * @var string
     *
     */
    public $oauthAccessToken;
    /**
     * @access private
     * @var string
     *
     */
    public $oauthAccessTokenSecret;
    /**
     * @access private
     * @var string
     *
     */
    public $consumerKey;
    /**
     * @access private
     * @var string
     *
     */
    public $consumerSecret;
    /**
     * @access private
     * @var string
     *
     */
    public $getfield;
    /**
     * @access protected
     * @var string
     *
     */
    public $oauth;
    /**
     * @access public
     * @var string
     *
     */
    public $url;
    /**
     * @access protected
     * @var string
     *
     */
    public $baseUrl;
    /**
     * @access protected
     * @var string
     *
     */
    public $requestMethod;
    /**
     * @access protected
     * @var array
     *
     */
    public $searchParams;
    /**
     * @access private
     * @var array
     *
     */
    public $delimiter;
    /**
     * @access private
     * @var string
     *
     */
    public $numberOfPhotos;
    /**
     * @access private
     * @var string
     *
     */
    public $resultType;
    /**
     * @access private
     * @var bool
     *
     */
    public $setGeolocation;
    /**
     * @access private
     * @var string
     *
     */
    public $setLang;
    /**
     * @access private
     * @var string
     *
     */
    public $photoFilters;
    //for pagination
    /**
     * @access public
     * @var string
     *
     */
    public $maxId;
    /**
     * @access public
     * @var array
     *
     */
    public $uniqueRecords;
    /**
     * @access public
     * @var array
     *
     */
    public $data;

    /**
     * __construct
     *
     * needed: oauthAccessToken
     *         oauthAccessTokenSecret
     *         consumerKey
     *         consumerSecret
     *         screenName (optional)
     */
    public function __construct()
    {

        $this->oauthAccessToken         = OAUTH_ACCESS_TOKEN;
        $this->oauthAccessTokenSecret   = OAUTH_ACCESS_TOKEN_SECRET;
        $this->consumerKey              = CONSUMER_KEY;
        $this->consumerSecret           = CONSUMER_SECRET;
        $this->screenName               = SCREEN_NAME;

        //searches setup
        //The number of tweets to return per page, up to a maximum of 100. Defaults to 15.
        $this->numberOfPhotos           = 20;
        $this->resultType               = 'recent';//recent, popular, mix(default)
        $this->setLang                  = '';//restricts tweets to language(lang by ISO)
        $this->setGeolocation           = false; //restricting by geolocation
        $this->photoFilters             = 'filter=images&include_entities=true&trim_user=false&include_rts=false';


        if (!in_array('curl', get_loaded_extensions()))
        {
            throw new \Exception(CURL_MISSING);
        }

        //may be in settings array-add
        $this->baseUrl = "https://api.twitter.com/1.1/search/tweets.json";

        $this->delimiter = "?q=";

        $this->requestMethod = 'GET';
        $this->maxId = '';
        $this->data = [];
        $this->uniqueRecords = '';
    }
    /**
     * setSearchParams - we may search by tag
     *                   and geocode(with: latitud, longitude, distance)
     *                   optionally language
     *
     * @param array $params parameters we pass to the url
     *
     * @access public
     *
     * @return
     *
     */

    public function setSearchParams(array $params=null)
    {
        if(is_null($params)){
            throw new \Exception(MISSING_ARGUMENTS);
        }

        //passed parameters
        //max_id = str_id ->returns results less than max_id
        $array = ['tag','latitude','longitude','geocode','max_id'];

        foreach ( $params as $key => $value ) {
			if ( in_array( $key, $array ) && !empty($value)){
				$$key = $value;
			}
		}
        $stringArr = [];
        if(!empty($tag)){
            $stringArr[] = "#" . $tag;//hash tag might be ommited
        }
        if(!empty($max_id)){
            $stringArr[] = "max_id=" . $max_id;//search for less..than
        }
        if(!empty($since_id)){
            $stringArr[] = "since_id=" . $since_id;//search for - greater..than
        }
        if($this->setGeolocation == true ){
            //wise to add distance
            if(!empty($latitude) && !empty($longitude)){
                $stringArr[] = 'geocode=' . $latitude . ',' .$longitude;
            }
            if(!empty($geocode)){
                $stringArr[] = 'geocode=' . $geocode;
            }
        }
        if(!empty($this->setLang)){
            $stringArr[] = 'lang=' . $this->setLang;
        }
        if(!empty($this->resultType)){
            $stringArr[] = 'result_type=' . $this->resultType;
        }
        //number of photos and photo filters are crucial
        if(!empty($this->numberOfPhotos)){
            $stringArr[] = 'count=' . $this->numberOfPhotos;
        }
        if(!empty($this->photoFilters)){
            $stringArr[] = $this->photoFilters;
        }

        $string = implode('&', $stringArr);

        $string = $this->delimiter . $string ;
        $search = array('#', ',', '+', ':');
        $replace = array('%23', '%2C', '%2B', '%3A');
        $string = str_replace($search, $replace, $string);

        $this->searchParams = $string ;

        $this->buildOauth();
        return $this;
    }
    /**
     * getSearchParams Get built params
     *
     * @return string
     */
    public function getSearchParams()
    {
        //echo "just echoing\n" . $this->searchParams . "\n";
        return $this->searchParams;
    }
    /**
     * buildOauth
     *
     * @access public
     *
     * @return
     *
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     */
    public function buildOauth()
    {
        $oauth = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->oauthAccessToken,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );


        $getParams = $this->getSearchParams();

        if (!is_null($getParams)){
            $getfields = str_replace('?', '', explode('&', $getParams));
            foreach ($getfields as $g) {
                $split = explode('=', $g);
                if(isset($split[1])){
                    $oauth[$split[0]] = $split[1];
                }
            }
        }
        $baseInfo = $this->buildBaseString($this->baseUrl, $this->requestMethod, $oauth);

        $compositeKey = rawurlencode($this->consumerSecret) . '&' . rawurlencode($this->oauthAccessTokenSecret);
        $oauthSignature = base64_encode(hash_hmac('sha1', $baseInfo, $compositeKey, true));
        $oauth['oauth_signature'] = $oauthSignature;

        $this->url = $this->baseUrl;
        $this->oauth = $oauth;

        return $this;
    }

    /**
     * performRequest perform the actual data retrieval from the API
     *
     * @param boolean $return If true, returns data
     *
     * @access public
     *
     * @return string json If $return param is true, returns json data.
     *
     */
    public function goCurl($return = false)
    {
        $header = array($this->buildAuthorizationHeader($this->oauth), 'Expect:');

        $getParams = $this->getSearchParams();

        $options = array(
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_HEADER         => false,
            //CURLOPT_URL => $this->url . "?screen_name=". $this->screenName,
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false
        );

        if ($getParams !== ''){
            $options[CURLOPT_URL] .= $getParams;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);

        if(curl_exec($ch) === false){
            $exception = new \Exception('cUrl error: '.curl_error($ch));
            //throw $exception;
            error_log(__CLASS__.__METHOD__. 'Exception: '. 'cUrl error: '.curl_error($ch));
            return false;
        }

        $result = curl_exec($ch);
        curl_close($ch);
        if($return == true)
            $result = json_decode($result, true);
        else
            $result = json_decode($result);

        return !empty($result) ? $result : false;
    }

    /**
     * buildBaseString method to generate the base string used by cURL
     *
     * @param string $baseURI
     * @param string $method
     * @param array $params
     *
     * @access private
     *
     * @return string
     *
     */
    public function buildBaseString($baseURI, $method, $params)
    {
        $return = array();
        ksort($params);

        foreach($params as $key=>$value){
            $return[] = "$key=" . $value;
        }

        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
    }

    /**
     * buildAuthorizationHeader method to generate authorization header used by cURL
     *
     * @param array $oauth Array of oauth data generated by buildOauth()
     *
     * @access private
     *
     * @return string
     */
    public function buildAuthorizationHeader($oauth)
    {
        $return = 'Authorization: OAuth ';
        $values = array();

        foreach($oauth as $key => $value){
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $return .= implode(', ', $values);
        return $return;
    }
    /**
     * parseTwitterData parsing data from array, session setup, etc
     *
     * @param object $results
     *
     * @return array
     *
    */
    public function parseData($results='')
    {
        if(empty($results)) return false;

        $strIdArr = [];//all id_str
        $arr = []; //main array with parsed data

        $textArr = []; //for caption
        $uniqueRecords = []; //extracting only unique records, for first time these are all
        $notRecords = [];
        $res = $results->statuses;

        if(empty($res)) return false;

        $d = 0;
        foreach($res as $result){
            $strIdArr[] = $strId = $result->id_str;
            if (isset($result->entities->media)) {
                foreach ($result->entities->media as $m => $media) {
                    if($media->type != 'photo') continue;
                    if(!in_array($media->id_str, $uniqueRecords)){
                        if(isset($result->entities->hashtags)){

                            $tagsArr = []; //hashtags
                            $x=0;
                            foreach($result->entities->hashtags as $hash){
                                $tagsArr[$x] = strtolower($hash->text);
                                $x++;
                            }
                            $tagsArr = array_unique($tagsArr);
                            sort($tagsArr);
                            //$arr[$d]['tags'] = array_diff($tagsArr, helper::$stopwords);
                            $arr[$d]['tags'] = $tagsArr;
                            $arr[$d]['text'] = $result->text;
                        }

                        $arr[$d]['mediaUrl'] = $media_url = $media->media_url;
                        $arr[$d]['id'] = $media->id_str;
                        array_push($uniqueRecords, $media->id_str);
                    }
                    else{
                        //array_push($notRecords, $media->id_str);
                        //Log::info('record OUT1 ' . $media->id_str. ' ' . $not);
                    }
                }
            }
            $d++;

        }
        //-- save locally for comparison
        $this->uniqueRecords = $uniqueRecords;

        $this->maxId = $strIdArr[sizeof($strIdArr)-1];//check tests
        $this->data = $arr;
        return $this;
    }
}
