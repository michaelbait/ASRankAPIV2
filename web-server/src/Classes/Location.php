<?php

namespace App\Classes;

class Location 
{
    const UNKNOWN='<span class="asrank-unknown">unknown</span>';
    public $id = NULL;
    public $type = self::UNKNOWN;
    public $area = self::UNKNOWN;
    public $name = self::UNKNOWN;
    public $info = NULL;
    public $areas = array();

    public function __construct($type,$area,$info)
    {
        $this->type = $type;
        $this->area = $area;
        $this->info = $info;
        if ($type == "asn") {
            $this->id = $info->asn;
            $this->name = "AS $info->asn";
            array_push($this->areas,
                array(
                    "url"=>"/asns/$info->asn/neighbors",
                    "label"=>"neighbors"
                ),
                array(
                    "url"=>"/asns/$info->asn/as-core",
                    "label"=>"AS Core"
                )
            );
        } elseif ($type == "org") {
            $this->id = $info->id;
            $this->name = "$info->name";
            array_push($this->areas,
                array(
                    "url"=>"/orgs/$info->id/members",
                    "label"=>"members"
                ),
                array(
                    "url"=>"/orgs/$info->id/as-core",
                    "label"=>"AS Core"
                )
            );
        }
    }
}
