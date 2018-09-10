<?php

namespace App\Classes;

class Dataset{
    const UNKNOWN='<span class="asrank-unknown">unknown</span>';
    public $clique = array();
    public $date = null;
    public $addresses = 0;
    public $prefixes = 0;
    public $asns = 0;
    public $orgs = 0;
    public $address_family = null;

    public function __construct() {}

    public function GET_JSON() {
        $json = file_get_contents(getenv('RESTFUL_DATABASE_URL').'/ds/');
        if ($json != NULL)
        {
            $parsed = json_decode($json);
            if ($parsed->{'data'} != NULL)
            {
                $data = $parsed->{'data'};
                foreach ($data[0] as $key => $value) 
                {
                    if ($key == "number_asnes") {
                        $this->number_asns = $value;
                    } else {
                        $this->$key = $value;
                    }
                }
            }
        }
    }

    public function date_year_mon_day()
    {
        if ($this->date != null) {
            $year_mon_day = preg_split("(\d\d\d\d)(\d\d)(\d\d)",$this->date);
            return join("?", $year_mon_day);
        } else {
            return "";
        }
    }

    public function imported_year_mon_day() 
    {
        return date("Y-m-d ",$this->imported);
    }
}
