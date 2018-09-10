<?php
/**
 * Created by PhpStorm.
 * User: efanchik
 * Date: 27.08.18
 * Time: 16:00
 */

namespace App\Tests\Helper;

use App\Helper\FSUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FSUtilTest extends KernelTestCase{

  public function testReadTextFile()  {

    self::bootKernel();
    // returns the real and unchanged service container
    $container = self::$kernel->getContainer();

    // gets the special container that allows fetching private services
    $container = self::$container;

    $utils = new FSUtil();
    $file = $utils->readTextFile('/home/efanchik/PhpStormProjects/asrank/data/20180301/20180301.links.jsonl');
    foreach ($file as $line) {
      print($line);
    }

    $this->assertNull($file);
  }
}