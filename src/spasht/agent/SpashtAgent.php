<?php
/*
 Copyright (C) 2014-2018, Siemens AG
 Author: Daniele Fognini, Andreas Würl

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace Fossology\Spasht;

use Fossology\Lib\Agent\Agent;
use Fossology\Lib\Dao\UploadDao;
use Fossology\Lib\Dao\SpashtDao;
use GuzzleHttp\Client;

include_once(__DIR__ . "/version.php");

/**
 * @file
 * @brief Spasht agent source
 * @class SpashtAgent
 * @brief The Spasht agent
 */
class SpashtAgent extends Agent
{

  /** @var UploadDao $uploadDao
     * UploadDao object
     */
    private $uploadDao;

    /** @var SpashtDao $uploadDao
     * UploadDao object
     */
    private $spashtDao;


    function __construct()
    {
        parent::__construct(SPASHT_AGENT_NAME, AGENT_VERSION, AGENT_REV);
        $this->uploadDao = $this->container->get('dao.upload');
        $this->spashtDao = $this->container->get('dao.spasht');
    }

    /*
     * @brief Run Spasht Agent for a package
     * @param $uploadId Integer
     * @see Fossology::Lib::Agent::Agent::processUploadId()
     */
    function processUploadId($uploadId)
    {

      $itemTreeBounds = $this->uploadDao->getParentItemBounds($uploadId);
      $pfileFileDetails = $this->uploadDao->getPFileDataPerFileName($itemTreeBounds);

      $pfileSha1DetailsUpload = array();

      foreach($pfileFileDetails as $pfileDetail)
      {
        $pfileSha1DetailsUpload[] = json_encode($pfileDetail['sha1']);
      }

      $uploadAvailable = $this->searchUploadIdInSpasht($uploadId);

      if($uploadAvailable == false)
      {
        $file = fopen('/home/fossy/abc.json','w');
        fwrite($file,"no data available");
        fclose($file);

        return false;
      }

      $scancodeVersionAndUri = $this->getScanCodeVersion($uploadAvailable);

      $file = fopen('/home/fossy/abc.json','w');

      foreach($scancodeVersionAndUri as $key)
      {
        fwrite($file,$key['toolVersion']);
      }
      fclose($file);
        
      return true;
    }

    /**
     * This function is responsible for available upload in the spasht db.
     * If the upload is available then only the spasht agent will run.
     */

    protected function searchUploadIdInSpasht($uploadId)
    {
      $result = $this->spashtDao->getComponent($uploadId);

      if(!empty($result))
      {
        return $result;
      }

      return false;
    }

    /**
     * Get ScanCode Versions and Uri from harvest end point.
     * This collection will be used for filtering of harvest data.
     */

    protected function getScanCodeVersion($details)
    {
      $namespace = $details['spasht_namespace']; 
      $name = $details['spasht_name'];
      $revision = $details['spasht_revision'];
      $type = $details['spasht_type'];
      $provider = $details['spasht_provider'];

      $tool = "scancode";

      /** Guzzle/http Guzzle Client that connect with ClearlyDefined API */
      $client = new Client([
        // Base URI is used with relative requests
        'base_uri' => 'https://api.clearlydefined.io/',
        ]);

        // uri to harvest section in the api

      $uri = 'harvest/'.$type."/".$provider."/".$namespace."/".$name."/".$revision."/".$tool;

      $res = $client->request('GET',$uri,[]);

      if($res->getStatusCode()==200)
      {
        $body = json_decode($res->getBody()->getContents());

        if(sizeof($body) == 0)
        {
          return "Scancode not found!";
        }

        $result = array();
        
        for ($x = 0; $x < sizeof($body) ; $x++)
        {
          $str = explode ("/", $body[$x]);

          $temp = array();

          $toolVersion = $str[6];
          $newToolVersion = "";

          for ($y = 0; $y < strlen($toolVersion); $y++)
          {
            if($toolVersion[$y] != ".")
            {
              $newToolVersion .= $toolVersion[$y];
            }
          }

          $temp['toolVersion'] = $toolVersion;
          $temp['newToolVersion'] = $newToolVersion;
          $temp['uriToolVersion'] = $body[5];

          $result[] = $temp;
        }

        return $result;
      }

      return "scancode not found!";
    }

}
