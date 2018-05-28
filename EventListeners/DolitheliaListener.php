<?php

namespace Dolithelia\EventListeners;
use \PDO;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Event\TheliaFormEvent;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\HttpFoundation\Request;

use Thelia\Model\Base\ProductSaleElementsQuery;


use Thelia\Model\ConfigQuery;


class DolitheliaListener extends BaseAction implements EventSubscriberInterface
{
  public static function getSubscribedEvents()
  {
      return [
          TheliaEvents::AFTER_CREATECUSTOMER => array("sendCust", 1),
          TheliaEvents::CUSTOMER_UPDATEACCOUNT => array("updateCustomer", 3),
      ];
  }

  public function CallAPI($method, $domainask, $data = false)
  {
    $curl = curl_init();
    $httpheader = ['DOLAPIKEY: '.ConfigQuery::read('Dolithelia_api_key')];
      $url = ConfigQuery::read('Dolithelia_base_url').$domainask;
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            $httpheader[] = "Content-Type:application/json";
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
	          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            $httpheader[] = "Content-Type:application/json";
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	  curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}


  public function sendCust(CustomerEvent $event)
  {
    $client=$event->getCustomer();
    $adr=$client->getAddresses();
    if(is_null($adr[0]->getPhone()) == false )
    {
      $tel=$adr[0]->getPhone();
    }
    else if( is_null($adr[0]->getCellphone()) == false){
      $tel=$adr[0]->getCellphone();
    }
    else{
      $tel=null;
    }

    $completeAdressObject = $customer->getAddressesJoinCountry();
    $countryObject = $adr[0]->getCountry();
    $countryName = $countryObject->getTitle();
    $countrySearch = json_decode( $this->CallAPI("GET", "dictionarycountries", array(
        "sortfield" => "t.label",
        "sortorder" => "ASC",
        "limit" => "1",
        "sqlfilters" => "(t.label:like:'".$countryName."%')",
        "lang"=>"en_US"
        )
      ), true);
      if(!empty($countrySearch[0])){
        $countryIDDolibarr = $countrySearch[0]["id"];
      }
      else {
        $countryIDDolibarr = 1;
      }

    $newClient = [
    "name" 			=> $client->getFirstname()." ".$client->getLastname(),
		"email"			=> $client->getEmail(),
    "phone"     => $tel,
    "address"   => $adr[0]->getAddress1().' '.$adr[0]->getAddress2().' '.$adr[0]->getAddress3(),
    "zip"          =>$adr[0]->getZipcode(),
    "town"          =>$adr[0]->getCity(),
    "country_id" => $countryIDDolibarr,
		"client" 		=> "1",
    "fournisseur" => "0",
		"code_client"	=> $client->getRef()
    ];
	 $newClientResult = $this->CallAPI("POST","thirdparties", json_encode($newClient));

   $newClientResult = json_decode($newClientResult, true);
	 $clientDoliId = $newClientResult;


  }


  public function updateCustomer(CustomerCreateOrUpdateEvent $event)
  {
        file_put_contents("poiodfsqfs0.txt","Update function running");
    $customer = $event->getCustomer();

    $clientSearch = json_decode( $this->CallAPI("GET", "thirdparties", array(
        "sortfield" => "t.code_client",
        "sortorder" => "ASC",
        "limit" => "1",
        "sqlfilters" => "(t.code_client:=:'".$customer->getRef()."')"
        )
      ), true);
      $adr=$customer->getAddresses();
      if(is_null($adr[0]->getPhone()) == false )
      {
        $tel=$adr[0]->getPhone();
      }
      else if( is_null($adr[0]->getCellphone()) == false){
        $tel=$adr[0]->getCellphone();
      }
      else{
        $tel=null;
      }
      $completeAdressObject = $customer->getAddressesJoinCountry();
      $countryObject = $adr[0]->getCountry();
      $countryName = $countryObject->getTitle();
      $countrySearch = json_decode( $this->CallAPI("GET", "dictionarycountries", array(
          "sortfield" => "t.label",
          "sortorder" => "ASC",
          "limit" => "1",
          "sqlfilters" => "(t.label:like:'".$countryName."%')",
          "lang"=>"en_US"
          )
        ), true);
        if(!empty($countrySearch[0])){
          $countryIDDolibarr = $countrySearch[0]["id"];
        }
        else {
          $countryIDDolibarr = 1;
        }

      $upadtedClient = [
      "name" 			=> $customer->getFirstname()." ".$customer->getLastname(),
      "email"			=> $customer->getEmail(),
      "phone"     => $tel,
      "address"   => $adr[0]->getAddress1().' '.$adr[0]->getAddress2().' '.$adr[0]->getAddress3(),
      "zip"          =>$adr[0]->getZipcode(),
      "town"          =>$adr[0]->getCity(),
      "client" 		=> "1",
      "fournisseur" => "0",
      "country_id" => $countryIDDolibarr,
      "code_client"	=> $customer->getRef()
      ];

      if(is_array($clientSearch) == true){
        $updatingClientResult = $this->CallAPI("PUT", "thirdparties/".$clientSearch[0]["id"], json_encode($upadtedClient));
        $updatingClientResult = json_decode($updatingClientResult, true);
      }
      else{
        $newClientResult = $this->CallAPI("POST","thirdparties", json_encode($upadtedClient));
        $newClientResult = json_decode($newClientResult, true);
        $clientDoliId = $newClientResult;
      }



  }

}
