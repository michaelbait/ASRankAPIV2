<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 05.09.18
 * Time: 13:11
 */

namespace App\Api2\Service;

use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;

use Psr\Log\LoggerInterface;


class AsnService {

  const MEASUREMENT = 'asn_info_v4';
  const RETENTION_POLICY = 'autogen';

  private $client;
  private $db_name;
  private $db_user;
  private $db_pass;
  private $db_policy;
  private $logger;

  /**
   * AsnsService constructor.
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
   * Return all asns rows (expanded data) from InfluxDB.
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
        'SELECT COUNT("asn_name") FROM "%s"', self::MEASUREMENT
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
        'SELECT COUNT("asn_name") FROM "%s"', self::MEASUREMENT
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
            $records[] = $row['asn'];
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
   * Return one asn from InfluxDB.
   *
   * @param $id
   * @return array
   */
  public function get_asn_by_id($id){
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
          'SELECT * FROM "%s" WHERE "asn"=\'%s\'', self::MEASUREMENT, $id
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

  public function get_asn_by_id_verbose($id){
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
          'SELECT * FROM "%s" WHERE "asn"=\'%s\'', self::MEASUREMENT, $id
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

  public function get_asn_by_name($name, $offset, $limit){
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try{
      if(!is_null($name)){
        $database = $this->getDatabase();
        $query = sprintf(
          'SELECT * FROM "%s" WHERE "asn_name" =~ /(?i)^%s*/ LIMIT %s OFFSET %s',
          self::MEASUREMENT, $name, $limit, $offset
        );
        $data_point = $database->query($query);
        if($data_point){
          $rows = $data_point->getPoints();
          $cnt = count($rows);
          if($cnt > 0){
            $result['data'] = $this->pb_asn_by_name($rows);
            $result['total'] = $cnt;
          }
        }
      }
    }catch (Exception $e){
      $this->logger->error($e->getMessage());
    }
    return $result;
  }

  /**
   * Parse and restructure dasns fields (verbose mode).
   *
   * @param $rows   - Array of dataset records
   * @return array  - Array of restructured dataset rows
   */
  private function parse_and_build_data($rows): array{
    $result = [];
    if($rows != null && is_array($rows)) {
      foreach($rows as $row){
        $el = [];
        $el['id'] = array_key_exists('asn', $row) ? $row['asn'] : "";
        $el['rank'] = array_key_exists('rank', $row) ? $row['rank'] : "";
        $el['name'] = array_key_exists('asn_name', $row) ? $row['asn_name'] : "";
        $el['source'] = array_key_exists('source', $row) ? $row['source'] : "";
        $el['country'] = array_key_exists('country', $row) ? $row['country'] : "";
        $el['org'] = array_key_exists('org_id', $row) ? $row['org_id'] : "";
        $el['latitude'] = array_key_exists('latitude', $row) ? $row['latitude'] : "";
        $el['longitude'] = array_key_exists('longitude', $row) ? $row['longitude'] : "";

        $cone = [];
        $cone['prefixes'] = array_key_exists('customer_cone_prefixes', $row) ? $row['customer_cone_prefixes'] : "";
        $cone['addresses'] = array_key_exists('customer_cone_addresses', $row) ? $row['customer_cone_addresses'] : "";
        $cone['asnes'] = array_key_exists('customer_cone_asnes', $row) ? $row['customer_cone_asnes'] : "";
        $el['cone'] = $cone;

        $degree = [];
        $degree['globals'] = array_key_exists('degree_global', $row) ? $row['degree_global'] : "";
        $degree['peers'] = array_key_exists('degree_peer', $row) ? $row['degree_peer'] : "";
        $degree['siblings'] = array_key_exists('degree_sibling', $row) ? $row['degree_sibling'] : "";
        $degree['customers'] = array_key_exists('degree_customer', $row) ? $row['degree_customer'] : "";
        $degree['transits'] = array_key_exists('degree_transit', $row) ? $row['degree_transit'] : "";
        $el['degree'] = $degree;

        $result[] = $el;
      }
    }
    return $result;
  }

  private function pb_asn_by_name($rows): array{
    $result = [];
    if($rows != null && is_array($rows)) {
      foreach ($rows as $row) {
        $el = [];
        $el['asn'] = array_key_exists('asn', $row) ? $row['asn'] : "";
        $el['name'] = array_key_exists('asn_name', $row) ? $row['asn_name'] : "";
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