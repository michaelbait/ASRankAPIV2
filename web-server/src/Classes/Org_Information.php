<?php

namespace App\Classes;

class Org_Information 
{
    const UNKNOWN='<span class="asrank-unknown">unknown</span>';

    public $id= self::UNKNOWN;
    public $name= self::UNKNOWN;
    public $rank = self::UNKNOWN;
    public $country= self::UNKNOWN;

    public $number_members= 0;
    public $members = array();

    public $cone = array(
        "addresses" => 0
        ,"orgs" => 0
        ,"prefixes" => 0
        ,"asns" => 0
    );

    public $degree = array(
        "asn" => array(
            "global" => 0
            ,"transit" => 0
        )
        ,"org" => array(
            "global" => 0
            ,"transit" => 0
        )
    );    

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function GET_JSON()
    {
        $json = file_get_contents(getenv('RESTFUL_DATABASE_URL').'/orgs/'.$this->id.'?populate=1');
        if ($json != NULL)
        {
            $parsed = json_decode($json);
            if ($parsed->{'data'} != NULL)
            {
                $data = $parsed->{'data'};

                foreach ($data as $key => $value) 
                {
                    if ($key == "cone") {
                        foreach ($value as $k => $v) {
                            $this->$key[$k] = $v;
                        }
                    }
                    else if ($key == "degree") 
                    {
                        foreach ($value as $k => $v) {
                            foreach ($v as $l => $m) {
                                $this->$key[$k][$l] = $m;
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
        return $this->name || $this->id;
    }

    public function get_json_ld()
    {
        $this->{'name'} = str_replace("\""," ",$this->{'name'});
        return json_encode($this);
    }
}
