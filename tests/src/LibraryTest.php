<?php

use Tesis\Socials\Twitter\Library;

require_once('config.php');

class LibraryTest extends PHPUnit_Framework_TestCase {

    public $library;
    public $lat;
    public $lng;
    public $tag;

    public function setUp()
    {
        parent::setUp();

        $this->searchParams = ['tag'=>'dogs'];
        $this->library = new Library;
    }
    public function tearDown()
    {
        //
    }

    /**
     * test_searchByLocation_Pass
     *
    */
    public function test_searchByTag_Pass()
    {
        try{
            $test = $this->library->searchByTag($this->searchParams);
            $this->assertNotEmpty(sizeof($test), 'Expected Pass');
        }
        catch(\Exception $e){
            print_r($e->getMessage());
        }

    }
    /**
     * test_searchByTag_Fail
     * @expectedException     \Exception
     * @expectedExceptionMessage Check arguments, seems not OK
    */
    public function test_searchByTag_Fail()
    {
        $test = $this->library->searchByTag();
        $this->assertNotEmpty(sizeof($test), 'Expected Fail');
    }
    /**
     * test_setSearchParams_Arg_Geolocation_Fail
     * no records :()
     *
     * @expectedException     \Exception
     *
    */
    public function test_setSearchParams_Arg_Geolocation_Fail()
    {
        $searchParams = ['tag'=>'#dog', 'geocode'=>'51.44534646, -12.46543, 500km'];

        $getData = $this->library->searchByTag($searchParams);
            throw new \Exception('no records found');
    }

}
