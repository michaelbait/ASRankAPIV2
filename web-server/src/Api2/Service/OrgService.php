<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 05.09.18
 * Time: 12:49
 */

namespace App\Api2\Service;

use App\Api2\Helper\LocaleHelper;
use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Intl\Intl;


class OrgService {
  const MEASUREMENT = 'org_info_v4';
  const RETENTION_POLICY = 'autogen';

  private $client;
  private $db_name;
  private $db_user;
  private $db_pass;
  private $db_policy;
  private $logger;
  private $lh;

  /**
   * LocationService constructor.
   * Automatically injects params
   * @param $influxdb_host - InfluxDB host name
   * @param $influxdb_port - InfluxDB port number
   * @param $influxdb_name - InfluxDB database name
   * @param $influxdb_user - InfluxDb user name
   * @param $influxdb_pass - InfluxDb user password
   * @param $influxdb_policy - InfluxDb retention policy
   * @param LoggerInterface $logger
   */
  public function __construct($influxdb_host,
                              $influxdb_port,
                              $influxdb_name,
                              $influxdb_user,
                              $influxdb_pass,
                              $influxdb_policy, LoggerInterface $logger){

    $this->client = new Client($influxdb_host, $influxdb_port);
    $this->db_name = $influxdb_name;
    $this->db_user = $influxdb_user;
    $this->db_pass = $influxdb_pass;
    $this->db_policy = $influxdb_policy;
    $this->logger = $logger;
    $this->lh = new LocaleHelper();
  }

  /**
   * Return all orgs (nonexpanded (only name) data) from InfluxDB.
   *
   * @param $offset - Offset for ISQL SELECT query.
   * @param $limit - Limit for ISQL SELECT query.
   * @return array
   */
  public function get_all($offset, $limit) {
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try {
      $database = $this->getDatabase();
      $total_point = $database->query(sprintf(
        'SELECT COUNT("org_name") FROM "%s"', self::MEASUREMENT
      ));
      $data_point = $database->query(sprintf(
        'SELECT org_name FROM "%s" LIMIT %s OFFSET %s', self::MEASUREMENT,  $limit, $offset
      ));

      if($total_point && $data_point) {
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $result['total'] = $total;
        $result['data'] = $this->pb_data($rows);
      }

    }catch (Exception $e) {
      $result['status'] = "failure";
      $result['error'] = [
        "type" => "UNKNOWN",
        "description" => $e->getMessage()
      ];
      $this->logger->error($e->getMessage());
    }
    return $result;
  }

  /**
   * Return all orgs (expanded data) from InfluxDB.
   *
   * @param $offset - Offset for ISQL SELECT query.
   * @param $limit - Limit for ISQL SELECT query.
   * @return array
   */
  public function get_all_verbose($offset, $limit) {
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try {
      $database = $this->getDatabase();
      $total_point = $database->query(sprintf(
        'SELECT COUNT("org_name") FROM "%s"', self::MEASUREMENT
      ));
      $data_point = $database->query(sprintf(
        'SELECT * FROM "%s" LIMIT %s OFFSET %s', self::MEASUREMENT,  $limit, $offset
      ));

      if($total_point && $data_point) {
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $result['total'] = $total;
        $result['data'] = $this->pb_data_verbose($rows);
      }

    }catch (Exception $e) {
      $result['status'] = "failure";
      $result['error'] = [
        "type" => "UNKNOWN",
        "description" => $e->getMessage()
      ];
      $this->logger->error($e->getMessage());
    }
    return $result;
  }


  /**
   * Parse and restructure orgs fields (simple mode).
   *
   * @param $rows   - Array of orgs records
   * @return array  - Array of restructured orgs rows
   */
  private function pb_data($rows): array{
    $result = [];
    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $result[] = array_key_exists('org_name', $row) ? $row['org_name'] : "";
      }
    }
    return $result;
  }

  /**
   * Parse and restructure orgs fields (verbose mode).
   *
   * @param $rows   - Array of orgs records
   * @return array  - Array of restructured orgs rows
   */
  private function pb_data_verbose($rows): array{
    $result = [];
    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $el = [];
        $el["id"] = array_key_exists('org_id', $row) ? $row['org_id'] : "";
        $el["name"] = array_key_exists('org_name', $row) ? $row['org_name'] : "";
        $el["country"] = array_key_exists('country', $row) ? $row['country'] : "";
        $country = $this->lh->get_country($el["country"]);
        $el["country_name"] = $country;
        $el["rank"] = array_key_exists('rank', $row) ? $row['rank'] : "";
        $el["number_members"] = array_key_exists('number_members', $row) ? $row['number_members'] : "";
        $el["members"] = array_key_exists('members', $row) ? json_decode($row['number_members']) : [];

        $cone = [];
        $cone['prefixes'] = array_key_exists('customer_cone_prefixes', $row) ? $row['customer_cone_prefixes'] : "";
        $cone['addresses'] = array_key_exists('customer_cone_addresses', $row) ? $row['customer_cone_addresses'] : "";
        $cone['asns'] = array_key_exists('customer_cone_asnes', $row) ? $row['customer_cone_asnes'] : "";
        $cone['orgs'] = array_key_exists('customer_cone_orgs', $row) ? $row['customer_cone_orgs'] : "";
        $el['cone'] = $cone;

        $degree = [];
        $degree['asn'] = [
          "transit" => array_key_exists('asn_degree_transit', $row) ? $row['asn_degree_transit'] : "",
          "global" => array_key_exists('asn_degree_global', $row) ? $row['asn_degree_global'] : ""
        ];
        $el['degree'] = $degree;
        $result[] = $el;
      }
    }
    return $result;
  }

  /**
   * Return InfluxDB Client Object
   *
   * @return Database - InfluxDb Clien Object
   * @throws Database\Exception
   */
  private function getDatabase(): Database {
    $database = $this->client->selectDB($this->db_name);
    if (!$database->exists()) {
      $database->create(new RetentionPolicy($this->db_name, $this->db_policy, 1, true));
    }
    return $database;
  }



}