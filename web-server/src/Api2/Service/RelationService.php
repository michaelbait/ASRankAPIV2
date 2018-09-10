<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 07.09.18
 * Time: 14:10
 */

namespace App\Api2\Service;

use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use Psr\Log\LoggerInterface;

class RelationService {

  const MEASUREMENT = 'asn_rel_v4';
  const RETENTION_POLICY = 'autogen';

  private $client;
  private $db_name;
  private $db_user;
  private $db_pass;
  private $db_policy;
  private $logger;

  /**
   * RelationService constructor.
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
  }

  /**
   *  Return all relations rows (expanded data) from InfluxDB.
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
        'SELECT COUNT("city") FROM "%s"', self::MEASUREMENT
      ));
      $data_point = $database->query(sprintf(
        'SELECT * FROM "%s" LIMIT %s OFFSET %s', self::MEASUREMENT,  $limit, $offset
      ));

      if($total_point && $data_point) {
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $result['total'] = $total;
        $result['data'] = $this->parse_and_build_data($rows);
      }
    } catch (Exception $e) {
      $result['status'] = "failure";
      $result['error'] = [
        "type" => "UNKNOWN",
        "description" => $e->getMessage()
      ];
      $this->logger->error($e->getMessage());
    }
    return $result;
  }

  public function get_filtered_by_asn($asn, $offset, $limit) {
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    if(!is_null($asn)){
      try {
        $database = $this->getDatabase();
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" WHERE asn = \'%s\' LIMIT %s OFFSET %s', self::MEASUREMENT, $asn, $limit, $offset
        ));

        if($data_point) {
          $rows = $data_point->getPoints();
          $result['data'] = $this->parse_and_build_data($rows);
        }
      } catch (Exception $e) {
        $result['status'] = "failure";
        $result['error'] = [
          "type" => "UNKNOWN",
          "description" => $e->getMessage()
        ];
        $this->logger->error($e->getMessage());
      }
    }

    return $result;
  }

  public function get_ranged_asns($asn1, $asn2, $offset, $limit) {
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    if(!is_null($asn1 && !is_null($asn2))){
      try {
        $database = $this->getDatabase();
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" WHERE asn = \'%s\' LIMIT %s OFFSET %s', self::MEASUREMENT, $asn1, $limit, $offset
        ));

        if($data_point) {
          $rows = $data_point->getPoints();
          $result['data'] = $this->parse_and_build_data($rows);
        }
      } catch (Exception $e) {
        $result['status'] = "failure";
        $result['error'] = [
          "type" => "UNKNOWN",
          "description" => $e->getMessage()
        ];
        $this->logger->error($e->getMessage());
      }
    }
    return $result;
  }

  /**
   * Parse and restructure realtions fields.
   *
   * @param $rows   - Array of dataset records
   * @return array  - Array of restructured dataset rows
   */
  private function parse_and_build_data($rows): array{
    $result = [];
    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $el = [];
        $el['relationship'] = array_key_exists('relationship', $row) ? $row['relationship'] : "";
        $el['asn'] = array_key_exists('asn', $row) ? $row['asn'] : "";
        $el['paths'] = array_key_exists('number_paths', $row) ? $row['number_paths'] : "";
        $el['locations'] = array_key_exists('locations', $row) ? $row['locations'] : "";

        $result[] = $el;
      }
    }
    return $result;
  }

  /**
   * Return InfluxDB Database Client
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