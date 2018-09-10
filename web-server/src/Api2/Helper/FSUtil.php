<?php

/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 27.08.18
 * Time: 15:19
 */

declare(strict_types=1);

namespace App\Api2\Helper;

use Monolog\Logger;
use Symfony\Component\Filesystem\Exception\IOException;

class FSUtil{
  private $logger;

  public function __construct(){
    $this->logger = new Logger("fsu_logger");
  }


  public function readTextFile($file){
    $opmode = "r";
    $fh =  null;

    try{
      $fh = fopen($file, $opmode);
      while($line = fgets($fh)){
        yield $line;
      }

    }catch (IOException $e){
      $this->logger->error($e->getMessage());

    }finally{
      if($fh !== null){
        fclose($fh);
      }
    }
  }

}