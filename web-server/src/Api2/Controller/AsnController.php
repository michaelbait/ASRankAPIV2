<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 05.09.18
 * Time: 13:11
 */

namespace App\Api2\Controller;

use App\Api2\Service\RelationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Swagger\Annotations as SWG;

use App\Api2\Helper\ReqUtils;
use App\Api2\Service\AsnService;

/**
 * @Route("/api/v2/asns")
 * @Route("/api/v2/asns/")
 */
class AsnController extends AbstractController {

  private $service;
  private $relation;
  private $reqUtils;
  private $perpage;
  private $page;

  public function __construct(int $perpage, int $page,
                              AsnService $service, RelationService $relation, ReqUtils $reqUtils){
    $this->service = $service;
    $this->relation = $relation;
    $this->perpage = $perpage;
    $this->page = $page;
    $this->reqUtils = $reqUtils;
  }

  /**
   * Get all asns.
   *
   * @Route("/", methods={"GET"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Returns all asns.")
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand asn information")
   *
   * @param Request $request
   * @return bool|false|float|int|string
   */
  public function asns(Request $request) {
    $params = [];

    $v = $request->get("verbose");
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list($offset, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    if(!is_null($v)) {
      $data = $this->service->get_all($offset, $limit);
    }else{
      $data = $this->service->get_all_ids_only($offset, $limit);
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
   * Get one asn specified by id.
   *
   * @Route("/{id}", methods={"GET"}, requirements={"asn"="[\d]+"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one asn specified by placeholder {id}."
   * )
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand  asn information.")
   *
   * @param $id   - Specific asn id
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function asn($id, Request $request) {
    $params = [];

    $v = $request->get("verbose");
    $did = ($id != null) ? $id : "default";
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    if(!is_null($v)){
      $data = $this->service->get_asn_by_id_verbose($did);
    }else{
      $data = $this->service->get_asn_by_id($did);
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
   * Get the links involving the given ASN.
   *
   * @Route("/{id}/links", methods={"GET"}, requirements={"asn"="[\d]+"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one asn specified by placeholder {id}."
   * )
   *
   * @param $id   - Specific asn id
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function asn_links($id, Request $request) {
    $params = [];

    $did = ($id != null) ? $id : "default";
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    $data = $this->relation->get_filtered_by_asn($did, $page, $limit);

    $params['status'] = 'success';
    $params['type'] = $data['type'];
    $params['data'] = $data['data'];
    $params['total'] = count($data['data']);
    $params['page_size'] = $limit;
    $params['page_number'] = $page;

    $result = $this->reqUtils->make_api2_response_data($params, $request);
    return $this->json($result);
  }

  /**
   * Get the links between two ASNs.
   *
   * @Route("/{id1}/links/{id2}", methods={"GET"}, requirements={"id1"="[\d]+", "id2"="[\d]+"})
   * @Route("/links/{id1}/{id2}", methods={"GET"}, requirements={"id1"="[\d]+", "id2"="[\d]+"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one asn specified by placeholder {id}."
   * )
   *
   * @param $id   - Specific asn id
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function asn_ranged_links($id1, $id2, Request $request) {
    $params = [];

    $did1 = ($id1 != null) ? $id1 : "1";
    $did2 = ($id1 != null) ? $id1 : "1000";
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    $data = $this->relation->get_filtered_by_asn($did1, $did2, $page, $limit);

    $params['status'] = 'success';
    $params['type'] = $data['type'];
    $params['data'] = $data['data'];
    $params['total'] = count($data['data']);
    $params['page_size'] = $limit;
    $params['page_number'] = $page;

    $result = $this->reqUtils->make_api2_response_data($params, $request);
    return $this->json($result);
  }

  /**
   * Get the ASN by name.
   *
   * @Route("/{name}/level", methods={"GET"}, requirements={"name"="(.+)"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one asn specified by name {name}."
   * )
   *
   * @param $id   - Specific asn id
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function asn_by_name($name, Request $request){
    $params = [];
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");
    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    $data = $this->service->get_asn_by_name($name, $page, $limit);
    $params['status'] = 'success';
    $params['type'] = $data['type'];
    $params['data'] = $data['data'];
    $params['total'] = count($data['data']);
    $params['page_size'] = $limit;
    $params['page_number'] = $page;

    $result = $this->reqUtils->make_api2_response_data($params, $request);

    return $this->json($result);
  }


}