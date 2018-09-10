<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 05.09.18
 * Time: 12:47
 */

namespace App\Api2\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Swagger\Annotations as SWG;

use App\Api2\Service\OrgService;
use App\Api2\Helper\ReqUtils;
use App\Api2\Service\DatasetService;

/**
 * @Route("/api/v2/orgs")
 * @Route("/api/v2/orgs/");
 */
class OrgController extends AbstractController {

  private $locationService;
  private $reqUtils;
  private $perpage;
  private $page;

  public function __construct(int $perpage, int $page, OrgService $locationService, ReqUtils $reqUtils){
    $this->locationService = $locationService;
    $this->perpage = $perpage;
    $this->page = $page;
    $this->reqUtils = $reqUtils;
  }

  /**
   * All organizations request.
   *
   * @Route("/", methods={"GET"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Returns all lorganizations.")
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand organization information")
   *
   * @param Request $request
   * @return bool|false|float|int|string
   */
  public function orgs(Request $request) {}

}