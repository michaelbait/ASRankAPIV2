<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 29.08.18
 * Time: 11:17
 */

namespace App\Api2\Service;

use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;

use Psr\Log\LoggerInterface;


class DatasetService{
  const MEASUREMENT = 'dataset_info_v4';
  const RETENTION_POLICY = 'autogen';

  private $client;
  private $db_name;
  private $db_user;
  private $db_pass;
  private $db_policy;
  private $logger;

  /**
   * DatasetService constructor.
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

  public function get_all_ids_only($offset, $limit) {
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try{
      $database = $this->getDatabase();
      $total_point = $database->query(sprintf(
        'SELECT COUNT("dataset_id") FROM "%s"', self::MEASUREMENT
      ));
      $data_point = $database->query(sprintf(
        'SELECT * FROM "%s" LIMIT %s OFFSET %s', self::MEASUREMENT,  $limit, $offset
      ));

      if($total_point && $data_point){
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $records = [];
        if(count($rows) > 0){
          foreach ($rows as $row){
            $records[] = $row['dataset_id'];
          }
        }
        sort($records);

        $result['total'] = $total;
        $result['data'] = $records;
      }
    }catch (Exception $e){
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
   * Return all dataset rows from InfluxDB.
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
        'SELECT COUNT("dataset_id") FROM "%s"', self::MEASUREMENT
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
   * Return one dataset from InfluxDB.
   *
   * @param $lid
   * @return array
   * @throws Database\Exception
   */
  public function get_ds_by_id($id){
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try {
      $database = $this->getDatabase();

      if(is_numeric($id)) {
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" WHERE "dataset_id"=\'%s\'', self::MEASUREMENT, $id
        ));
      }else{
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" ORDER BY time DESC LIMIT 1', self::MEASUREMENT
        ));
      }

      if($data_point){
        $rows = $data_point->getPoints();
        if(count($rows) > 0){
          $result['data'] = $this->parse_and_build_data($rows)[0];
          $result['total'] = 1;
        }
      }

    }catch (Exception $e){
      $result['status'] = "failure";
      $result['error'] = [
        "type" => "UNKNOWN",
        "description" => $e->getMessage()
      ];
      $this->logger->error($e->getMessage());
    }
    return $result;
  }

  public function get_ds_by_id_verbose($id){
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try {
      $database = $this->getDatabase();

      if(is_numeric($id)) {
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" WHERE "dataset_id"=\'%s\'', self::MEASUREMENT, $id
        ));
      }else{
        $data_point = $database->query(sprintf(
          'SELECT * FROM "%s" ORDER BY time DESC LIMIT 1', self::MEASUREMENT
        ));
      }

      if($data_point){
        $rows = $data_point->getPoints();
        if(count($rows) > 0){
          $result['data'] = $this->parse_and_build_data_verbose($rows)[0];
          $result['total'] = 1;
        }
      }

    }catch (Exception $e){
      $result['status'] = "failure";
      $result['error'] = [
        "type" => "UNKNOWN",
        "description" => $e->getMessage()
      ];
      $this->logger->error($e->getMessage());
    }
    return $result;

  }

  // PRIVATE SECTIONS

  /**
   * Parse and restructure dataset fields.
   *
   * @param $rows   - Array of dataset records
   * @return array  - Array of restructured dataset rows
   */
  private function parse_and_build_data($rows): array{
    $result = array();

    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $el = array();
        $el['dataset_id'] = array_key_exists('dataset_id', $row) ? $row['dataset_id'] : "";
        $el['date'] = array_key_exists('date', $row) ? json_decode($row['date']) : "";
        $el['addressFamily'] = array_key_exists('address_family', $row) ? $row['address_family'] : "";
        $el['asns'] = array_key_exists('number_asnes', $row) ? json_decode($row['number_asnes']) : "";
        $el['orgs'] = array_key_exists('number_organizes', $row) ? json_decode($row['number_organizes']) : "";
        $el['prefixes'] = array_key_exists('number_prefixes', $row) ? json_decode($row['number_prefixes']) : "";
        $el['addresses'] = array_key_exists('number_addresses', $row) ? json_decode($row['number_addresses']) : "";
        $el['clique'] = array_key_exists('clique', $row) ? json_decode($row['clique']) : [];
        $el['time'] = array_key_exists('time', $row) ? $row['time'] : "";
        $result[] = $el;
      }
    }
    return $result;
  }

  /**
   * Parse and restructure dataset fields (verbose mode).
   *
   * @param $rows   - Array of dataset records
   * @return array  - Array of restructured dataset rows
   */
  private function parse_and_build_data_verbose($rows): array{
    $result = array();

    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $el = array();
        $el['id'] = array_key_exists('dataset_id', $row) ? $row['dataset_id'] : "";
        $el['address_family'] = array_key_exists('address_family', $row) ? $row['address_family'] : "";
        $el['asn_assigned_ranges'] = array_key_exists('asn_assigned_ranges', $row) ? json_decode($row['asn_assigned_ranges']) : [];
        $el['asn_reserved_ranges'] = array_key_exists('asn_reserved_ranges', $row) ? json_decode($row['asn_reserved_ranges']) : [];
        $el['prefixes'] = array_key_exists('number_prefixes', $row) ? json_decode($row['number_prefixes']) : "";
        $el['addresses'] = array_key_exists('number_addresses', $row) ? json_decode($row['number_addresses']) : "";
        $el['asns'] = array_key_exists('number_asnes', $row) ? json_decode($row['number_asnes']) : "";
        $el['orgs'] = array_key_exists('number_organizes', $row) ? json_decode($row['number_organizes']) : "";
        $el['date'] = array_key_exists('date', $row) ? json_decode($row['date']) : "";
        $el['clique'] = array_key_exists('clique', $row) ? json_decode($row['clique']) : [];
        $el['sources'] = array_key_exists('sources', $row) ? json_decode($row['sources']) : [];
        $el['time'] = array_key_exists('time', $row) ? $row['time'] : "";
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