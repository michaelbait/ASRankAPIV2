<?php

namespace App\Classes;

class ASN_Information 
{
    const UNKNOWN='<span class="asrank-unknown">unknown</span>';
    public $rank = self::UNKNOWN;
    public $asn = -1;
    public $country = self::UNKNOWN;
    public $name = self::UNKNOWN;
    public $cone = array(
        "addresses" => 0
        ,"prefixes" => 0
        ,"asns" => 0
    );
    public $degree = array(
        "transits" => 0
        ,"customers" => 0
        ,"providers" => 0
        ,"peers" => 0
        ,"siblings" => 0
        ,"globals" => 0
    );
    public $longitude = null;
    public $latitude = null;
    public $clique = false;
    public $org = array(
        "id" => null
        ,"name" => self::UNKNOWN
    );
    public function __construct($as_num)
    {
        $this->asn = $as_num;
    }

    public function GET_JSON()
    {
        $json = file_get_contents(getenv('RESTFUL_DATABASE_URL').'/asns/'.$this->asn.'?populate=1');
        if ($json != NULL)
        {
            $parsed = json_decode($json);
            if ($parsed->{'data'} != NULL)
            {
                $data = $parsed->{'data'};
                foreach ($data as $key => $value) 
                {
                    if ($key == "degree" || $key == "org" || $key == 'cone') 
                    {
                        foreach ($value as $k => $v) {
                            if ($k == "degree_sibling") {
                                $this->degree['siblings'] = $v;
                            } else {
                                $this->$key[$k] = $v;
                            }
                        }
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    public function get_name()
    {
        if ($this->org['name'] != NULL && $this->org['name'] != self::UNKNOWN) {
            return $this->org['name'];
        }
        if ($this->name != NULL && $this->name != self::UNKNOWN) {
            return $this->name;
        }
        return null;
    }

    public function get_json_ld()
    {
        return json_encode($this);
    }
}
