<?php
/**
 * Created by PhpStorm.
 * User: baitaluk
 * Date: 28.08.18
 * Time: 11:19
 */

namespace App\Api2\Service;

use Exception;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;

use Psr\Log\LoggerInterface;


class LocationService{
  const MEASUREMENT = 'locations';
  const RETENTION_POLICY = 'autogen';

  private $client;
  private $db_name;
  private $db_user;
  private $db_pass;
  private $db_policy;
  private $logger;

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
  }

  /**
   * Return all locations from InfluxDB with id column only.
   *
   * @param $offset - Offset for ISQL SELECT query.
   * @param $limit  - Limit for ISQL SELECT query.
   * @return array
   */
  public function get_all_ids_only($offset, $limit) {
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

      if($total_point && $data_point){
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $records = [];
        if(count($rows) > 0){
          foreach ($rows as $row){
            $records[] = $row['lid'];
          }
        }
        sort($records);

        $result['total'] = $total;
        $result['data'] = $records;
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

  /**
   * Return all locations rows (expanded data) from InfluxDB.
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

      if($total_point && $data_point){
        $total = $total_point->getPoints()[0]['count'];
        $rows = $data_point->getPoints();

        $records = [];
        if(count($rows) > 0){
          foreach ($rows as $row){
            $elem = [];
            $elem['id'] = $row['lid'];
            $elem['city'] = $row['city'];
            $elem['country'] = $row['country'];
            $elem['continent'] = $row['continent'];
            $elem['region'] = $row['region'];
            $elem['population'] = $row['population'];
            $elem['latitude'] = $row['latitude'];
            $elem['longitude'] = $row['longitude'];
            $elem['time'] = $row['time'];
            $records[] = $elem;
          }
        }
        $result['total'] = $total;
        $result['data'] = $records;
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


  /**
   * Return one location from InfluxDB.
   *
   * @param $lid
   * @return array
   * @throws Database\Exception
   */
  public function get_loc_by_id($lid){
    $result = [
      'type' => self::MEASUREMENT,
      'total' => 0,
      'data'  => [],
      'status' => 'success'
    ];

    try {
      $database = $this->getDatabase();
      $data_point = $database->query(sprintf(
        'SELECT * FROM "%s" WHERE "lid"=\'%s\'', self::MEASUREMENT, $lid
      ));

      if($data_point){
        $rows = $data_point->getPoints();
        if(count($rows) > 0){
          $result['data'] = $rows[0];
        }
        $result['total'] = 1;
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