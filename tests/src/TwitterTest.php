<?php
use Tesis\Socials\Twitter\TwitterFetcher;

require_once('config.php');

class TwitterFetcherTest extends PHPUnit_Framework_TestCase {
    public $classRepo;
    public $twitter;
    public $settings;
    public function setUp()
    {
        parent::setUp();
        $this->classRepo = 'Tesis\Socials\Twitter\TwitterFetcher';
        // setup class
        $this->twitter = new TwitterFetcher();
    }
    public function tearDown()
    {
        //
    }
    /**
     * initial test to see if roots are working
     *
    */
    public function testProcess_If_Class_Has_Id_Variable()
    {
        $this->assertClassHasAttribute('oauthAccessToken', $this->classRepo, 'Expected Pass');
    }
    //--- test if all variables are defined in controller
    /**
     * test_If_Variables_for_Process_Defined
     *
     * @param $a variable to test
     * @param $expected the class we expected to be in
     *
     * @dataProvider variablesProvider
     *
    */
    public function test_If_Variables_for_Process_Defined($a, $expected)
    {
        $actual = $this->classRepo;

        $this->assertClassHasAttribute($a, $actual, 'Expected Pass');
    }
    /**
    *
    * variablesProvider
    *
    * a provider for test_If_Variables_for_Process_Defined
    *
    */
    public function variablesProvider()
    {
        return array(
            array('oauthAccessTokenSecret', $this->classRepo, 'Expected Pass'),
            array('consumerKey', $this->classRepo, 'Expected Pass'),
            array('consumerSecret', $this->classRepo, 'Expected Pass'),
            array('requestMethod', $this->classRepo, 'Expected Pass'),
            array('getfield', $this->classRepo, 'Expected Pass'),
            array('oauth', $this->classRepo, 'Expected Pass'),
            array('url', $this->classRepo, 'Expected Pass'),
            array('searchParams', $this->classRepo, 'Expected Pass'),
        );
    }
    /**
     * test_setSearchParams_withHash_Pass
     *
    */
    public function test_setSearchParams_withHash_Pass()
    {
        // Perform the request
        //$searchParams = ['latitude'=>'41.44534646', 'longitude'=>'12.46543', 'tag'=>'#dog'];
        $searchParams = ['tag'=>'#dog'];
        //?q=%23dog&lang=pt&result_type=recent&filter=images&include_entities=true

        $test = $this->twitter->setSearchParams($searchParams)
                              ->goCurl(true);//true->return array
                              //echo $test;
        $this->assertNotContains('error', $test, 'Expected pass');
    }
    /**
     * test_setSearchParams_multipleArgs_Fail
     *
    */
    public function test_setSearchParams_multipleArgs_Fail()
    {
        // Perform the request
        $searchParams = ['latitude'=>'41.44534646', 'longitude'=>'12.46543', 'tag'=>'#dog'];
        //?q=%23dog&lang=pt&result_type=recent&filter=images&include_entities=true

        $test = $this->twitter->setSearchParams($searchParams)
                              ->goCurl();
        //$this->assertArrayHasKey('errors', $test, 'Expected fail');

    }
    /**
     * test_getTagsMedia_Pass
     * MOST RELEVANT for photos search
     *
    */
    public function test_getTagsMedia_Pass()
    {
        $searchParams = ['tag'=>'#dog'];
        $getData = $this->twitter->setSearchParams($searchParams)
                              ->goCurl();

        $this->assertNotEmpty($getData, 'Expected Pass');
        $this->assertNotEquals(0, $getData, 'Expected Pass');
        //file_put_contents('tests/data.txt', print_r($getData, true));
        return $getData;
    }
    /**
     * test_getTagsMedia_Parse_Get_Param_For_Next_Search
     * @depends test_getTagsMedia_Pass
     *
    */
    public function test_getTagsMedia_Parse_Get_Param_For_Next_Search($results)
    {
        $this->assertInternalType('object', $results);
        $parse = $this->parseData($results);
        //echo $parse->maxId;
        $this->assertNotEmpty($parse->maxId);
        $this->assertInternalType('string', $parse->maxId);

        //return maxId to perform next search
        return $parse->maxId;
    }
    /**
     * test_getTagsMedia_NEXT_PASS
     * @depends test_getTagsMedia_Parse_Get_Param_For_Next_Search
     *
     * on run process, save last id (max_id) - searching less...than
     * using for next run right away
     * on first run - after a while (next day, ...) - could use since_id greater..than
     *
    */
    public function test_getTagsMedia_NEXT_PASS($maxId)
    {
        //build new arr to perform next search
        $searchParams = ['tag'=>'#dog', 'max_id' => $maxId];
        $getData = $this->twitter->setSearchParams($searchParams)
                              ->goCurl();

        $arr = $this->parseData($getData);
        $this->assertNotEmpty($arr->maxId);
        $this->assertInternalType('string', $arr->maxId);

        $lastArr = ['tag'=>'#dog', 'max_id' => $arr->maxId];

        return $getData;
    }
    /**
     * test_newRun_saving_different_results
     * @depends test_getTagsMedia_NEXT_PASS
     *
     * on second run - when parsing - compare saved ids for uniqueness
    */
    public function test_newRun_saving_different_results($getData)
    {
        $arr = $this->parseData($getData);
        $this->assertNotEmpty($arr->maxId);
        $this->assertInternalType('string', $arr->maxId);

    }
    /**
     * test_getTagsMedia_since_max_PASS
     *
    */
    public function test_getTagsMedia_since_max_PASS()
    {
        $searchParams = ['tag'=>'#dog', 'max_id' => '573383709207896064', 'since_id'=>'573383709207896064'];
        $getData = $this->twitter->setSearchParams($searchParams)
                              ->goCurl();

        $arr = $this->parseData($getData);
        //print_r($arr);
        /*$this->assertNotEmpty($arr->maxId);
        $this->assertInternalType('string', $arr->maxId);*/

        return $getData;
    }
    /**
     * test_compareArrays_if_all_different_Pass
     *
    */
    public function test_compareArrays_if_all_different_Pass()
    {
        $str_max1 = "573390017101475840,573390004803649536,573389539445751808,573389316849680384,573388895364112385,573388842255843328,573388819334074368,573388745983967233,573388743064752128,573388690413776897,573388635699085312,573387935871066112,573387639308619776,573387509457162240,573387340330082304,573387287171600385,573387088697032704,573386760069124096,573386689659342848,573386195792633856";
        $str_max2 = "573386076477210625,573385935691378688,573385658171068416,573385520346234880,573385493771116544,573385356726411265,573385174714621954,573385044938633217,573385004157308928,573384998096539648,573384985085792256,573384972364468225,573384805510852609,573384293680078848,573384106584748032,573384040990023680,573384038771236864,573383911796940800,573383748013711360,573383709207896064";
        $str_since = "573383389883043840,573383186652241920,573383102170574849,573382966312898561,573382913200410624,573382896918142976,573382886134575104,573382875208409088,573382874717556737,573382859001618432,573382810330902528,573382596786315264,573382504482095104,573382452124626944,573382430071103489,573382189091454976,573382180044447744,573382078651211776,573382030626406400,573382008300277760";

        $arr_max1 = explode(',', $str_max1);
        $arr_max2 = explode(',', $str_max2);
        $arr_since = explode(',', $str_since);

        $this->assertEmpty(array_intersect($arr_max1, $arr_max2) , 'Expected Pass');
        $this->assertEmpty(array_intersect($arr_max2, $arr_since) , 'Expected Pass');
        $this->assertEmpty(array_intersect($arr_max2, $arr_max1) , 'Expected Pass');
        $this->assertEmpty(array_intersect($arr_since, $arr_max2) , 'Expected Pass');
        $this->assertEmpty(array_intersect($arr_since, $arr_max1) , 'Expected Pass');

    }
    /**
     * test_compareArrays_merge_if_all_different_Pass
     * arrays based on 3 different searches - using max_id for next run + since_id on 3rd run
     * all are different, thus we get all results different
     *
     * CONCERN: how to get max_id:
     *
    */
    public function test_compareArrays_merge_if_all_different_Pass()
    {
        $str_max1 = "573390017101475840,573390004803649536,573389539445751808,573389316849680384,573388895364112385,573388842255843328,573388819334074368,573388745983967233,573388743064752128,573388690413776897,573388635699085312,573387935871066112,573387639308619776,573387509457162240,573387340330082304,573387287171600385,573387088697032704,573386760069124096,573386689659342848,573386195792633856";
        $str_max2 = "573386076477210625,573385935691378688,573385658171068416,573385520346234880,573385493771116544,573385356726411265,573385174714621954,573385044938633217,573385004157308928,573384998096539648,573384985085792256,573384972364468225,573384805510852609,573384293680078848,573384106584748032,573384040990023680,573384038771236864,573383911796940800,573383748013711360,573383709207896064";
        $str_since = "573383389883043840,573383186652241920,573383102170574849,573382966312898561,573382913200410624,573382896918142976,573382886134575104,573382875208409088,573382874717556737,573382859001618432,573382810330902528,573382596786315264,573382504482095104,573382452124626944,573382430071103489,573382189091454976,573382180044447744,573382078651211776,573382030626406400,573382008300277760";

        $arr_max1 = explode(',', $str_max1);
        $arr_max2 = explode(',', $str_max2);
        $arr_since = explode(',', $str_since);

        if(empty(array_intersect($arr_max1, $arr_max2)))
        {
            $merged = array_merge($arr_max1, $arr_max2);
            if(empty(array_intersect($merged, $arr_since)))
            {
                $merged = array_merge($merged, $arr_since);
            }
        }
        //echo 'SORTED: ';
        arsort($merged);//high to low: first=max_id we start search for recent, so there is no higher id
        //we need last id -> and use max_id .... next run right away
        //after few days - we start search for recent, but we might start to search for less than ... if we might have old records
        //if we've already grabbed all results -> then we can only start with recent - and nothing else

        //print_r($merged);

        $this->assertEquals(sizeof($merged), sizeof($arr_max1) + sizeof($arr_max2) + sizeof($arr_since), 'Expected pass' );

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
