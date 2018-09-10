<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 28.08.18
 * Time: 17:34
 */

namespace App\Api2\Helper;

use DateTime;

class ReqUtils {

  private $perpage;
  private $page;

  public function __construct(int $perpage, int $page) {
    $this->perpage = $perpage;
    $this->page = $page;
  }

  /**
   * Make to be digit.
   * @param $digit - Some symbol
   * @return int   - digit
   */
  public static function digit_sanitizer($digit) {
    return (!is_null($digit) && is_numeric($digit)) ? (int)$digit : 0;
  }


  /**
   * Correct page_size and page_number params for pagination.
   *
   * @param $page_size - Records per page
   * @param $page_number - Current page number
   * @return array - Return arrau with limit and offset for Influxdb Query
   */
  public function pagination_sanitize($page_size, $page_number): array {
    $result = array();
    $page_size = (!is_null($page_size) && is_numeric($page_size) && (int)$page_size > 0) ? (int)$page_size : $this->perpage;
    $page_number = (!is_null($page_number) && is_numeric($page_number) && (int)$page_number > 0) ? (int)$page_number : $this->page;
    $result[] = ($page_size * $page_number) - $page_size;
    $result[] = $page_size;
    $result[] = $page_number;
    return $result;
  }

  /** Build response structure for current user request.
   *
   * @param $info     - Array of data from controller
   * @param $request  - Http current request object
   * @return array    - Builded structure
   */
  public function make_api2_response_data($info, $request): array {
    $type   = key_exists('type', $info) ? $info['type'] : "unknown";
    $descr  = key_exists('description', $info) ? $info['description'] : "No information.";
    $status = key_exists('status', $info) ? $info['status'] : "success";
    $errors = key_exists('errors', $info) ? $info['errors'] : "null";
    $total  = key_exists('total', $info)  ? $info['total'] : 0;
    $data   = key_exists('data', $info) ? $info['data'] : [];
    $pagesize = key_exists('page_size', $info) ? $info['page_size'] : $this->perpage;
    $pagenumber = key_exists('page_snumber', $info) ? $info['page_snumber'] : $this->page;

    $result = [];
    $result["type"] = $type;
    $result["error"] = $errors;
    $result["metadata"]["status"] = $status;
    $result["metadata"]["description"] = $descr;
    $result["metadata"]["page_number"] = $pagenumber;
    $result["metadata"]["page_size"] = $pagesize;
    $result["metadata"]["total"] = $total;
    $result["metadata"]["query_time"] = (new DateTime())->format(DateTime::ISO8601);
    $result["query_parameters"] = $this->extract_request_params($request);
    $result["data"] = $data;

    return $result;
  }

  /**
   * Extract allowed params from request.
   * @param $request  - Http request
   * @return array    - Array of allowed params
   */
  private function extract_request_params($request): array{
    $result = [];
    $params = [ "user", "verbose", "sort", "date_start", "date_end", "ds" ];
    if(!is_null($request)){
      foreach($request->query->all() as $key => $val){
        if(!in_array($key, $params)){
          continue;
        }
        $result[$key] = $val;
      }
    }
    return $result;
  }

}