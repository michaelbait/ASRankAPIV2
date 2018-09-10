<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 29.08.18
 * Time: 10:58
 */

namespace App\Api2\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Swagger\Annotations as SWG;

use App\Api2\Helper\ReqUtils;
use App\Api2\Service\DatasetService;



/**
 * @Route("/api/v2/ds")
 * @Route("/api/v2/ds/")
 *
 */
class DatasetController extends AbstractController{

  private $datasetService;
  private $reqUtils;
  private $perpage;
  private $page;

  public function __construct(int $perpage, int $page, DatasetService $datasetService, ReqUtils $reqUtils){
    $this->datasetService = $datasetService;
    $this->perpage = $perpage;
    $this->page = $page;
    $this->reqUtils = $reqUtils;
  }

  /**
   * Get all datasets.
   *
   * @Route("/", methods={"GET"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Returns all datasets.")
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand dataset information")
   *
   * @param Request $request
   * @return bool|false|float|int|string
   */
  public function datasets(Request $request) {
    $params = [];

    $v = $request->get("verbose");
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list($offset, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    if(!is_null($v)) {
      $data = $this->datasetService->get_all($offset, $limit);
    }else{
      $data = $this->datasetService->get_all_ids_only($offset, $limit);
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
   * Get one dataset specified by id.
   *
   * @Route("/{id}", methods={"GET"}, requirements={"location"="[\d]+"})
   *
   * @SWG\Response(
   *     response=200,
   *     description="Return one dataset specified by placeholder {id}."
   * )
   *
   * @SWG\Parameter(
   *     name="verbose",
   *     in="query",
   *     type="string",
   *     description="Expand dataset information.")
   *
   * @param $id   - Specific dataset id
   * @param Request $request
   * @return Response
   * @throws \InfluxDB\Database\Exception
   */
  public function dataset($id, Request $request) {
    $params = [];

    $v = $request->get("verbose");
    $did = ($id != null) ? $id : "default";
    $page_size = $request->get("page_size");
    $page_number = $request->get("page_number");

    list(, $limit, $page) = $this->reqUtils->pagination_sanitize($page_size, $page_number);

    if(!is_null($v)){
      $data = $this->datasetService->get_ds_by_id_verbose($did);
    }else{
      $data = $this->datasetService->get_ds_by_id($did);
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


}