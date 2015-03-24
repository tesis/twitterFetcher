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

use Tesis\Socials\Twitter\TwitterFetcher;

require_once('config.php');

/**
 * Class Library
 *
 * PHP version 5.6
 *
 * @package    Photos
 * @author     Tereza Simcic <tereza.simcic@gmail.com>
 *
 * Library is a class that connects a project with
 * TwitterFetcher
 *
 */
class Library
{

    const SOURCE_NAME = 'Twitter';

    public function __construct()
    {
        //echo "Test";
    }
    /**
     * searchByTag
     *
     * @param array $searchParams array of parameters passed like:
     *                            tag, geolocation in form (latitude,longitude,distance),
     *                            or latitude, langitude, language
     *                            for phlow.source only tag is relevant
     *                            otherwise we may end up without results
     *
     * @access public
     *
     * @return array
     *
     * //there is an option to search by geolocation, but I not getting any results
     *
    */
    public function searchByTag(array $searchParams=null)
    {

        if(is_null($searchParams))
        {
            throw new \Exception(MISSING_ARGUMENTS);
        }

        $twitter = new TwitterFetcher;
        $res = $twitter->setSearchParams($searchParams)
                              ->goCurl();
        if(empty($res))
        {
            throw new \Exception(NO_RECORDS);
        }
        if(isset($res->errors[0]->code) == 32)
        {
            throw new \Exception('Authentication failed');
        }

        $arr = $twitter->parseData($res);

        return $arr ? $arr : false;

    }
}
