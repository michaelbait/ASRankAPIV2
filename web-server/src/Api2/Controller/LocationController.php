<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 24.08.18
 * Time: 14:06
 */

namespace App\Api2\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Api2\Helper\ReqUtils;
use App\Api2\Service\LocationService;

use Swagger\Annotations as SWG;


/**
 * @Route("/api/v2/locations")
 * @Route("/api/v2/locations/")
 */
class LocationController extends AbstractController{

  private $locationService;
  private $reqUtils;
  private $perpage;
  private $page;

  public function __construct(int $perpage, int $page, LocationService $locationService, ReqUtils $reqUtils){
    $this->locationService = $locationService;
    $this->perpage = $perpage;
    $this->page = $page;
    $this->reqUtils = $reqUtils;
  }
  /**
   * All locations request.
   *
   * @Route("/", methods={"GET"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Returns all locations.")
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand location information")
   *
   * @param Request $request
   * @return bool|false|float|int|string
   */
  public function locations(Request $request) {
    $params = [];

    $v = $request->get("verbose");
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list($offset, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    if(!is_null($v)) {
      $data = $this->locationService->get_all($offset, $limit);
    }else{
      $data = $this->locationService->get_all_ids_only($offset, $limit);
    }
    $params['status'] = 'success';
    $params['type'] = $data['type'];
    $params['data'] = $data['data'];
    $params['total'] = $data['total'];
    $params['page_size'] = $limit;
    $params['page_number'] = $page;

    $result = $this->reqUtils->make_api2_response_data($params, $request);
    return $this->json($result);
  }

  /**
   * One specific location request.
   *
   * @Route("/{location}", methods={"GET"}, requirements={"location"="[^.]+"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one location specified by placeholder {location}."
   * )
   *
   * @param $location - Specific location name
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function location($location, Request $request){
    $params =  [];

    $loc = ($location != null && !empty($location)) ? $location : "";

    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    $data = $this->locationService->get_loc_by_id($loc);

    $params['status'] = 'success';
    $params['type'] = $data['type'];
    $params['data'] = $data['data'];
    $params['total'] = $data['total'];
    $params['page_size'] = $limit;
    $params['page_number'] = $page;

    $result = $this->reqUtils->make_api2_response_data($params, $request);
    return $this->json($result);
  }




}